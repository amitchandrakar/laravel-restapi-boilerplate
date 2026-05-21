<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportCandidatesFromCsvJob;
use App\Models\User;
use App\Support\CacheKeys;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Parses and imports basic candidate rows from admin CSV uploads.
 */
class CandidateCsvImportService
{
    /** @var list<string> */
    public const REQUIRED_HEADERS = ['email'];

    /** @var list<string> */
    public const OPTIONAL_HEADERS = [
        'first_name',
        'last_name',
        'phone',
        'gender',
        'marital_status',
        'profile_status',
        'password',
    ];

    public function __construct(private readonly CandidateUserService $candidateUserService) {}

    /**
     * @return array{import_id: string, queued: bool, total_rows: int, summary?: array<string, mixed>}
     */
    public function importFromUpload(UploadedFile $file, int $actorId): array
    {
        $parsed = $this->parseCsv($file);
        $totalRows = count($parsed['rows']);
        $syncMax = max(1, (int) config('api.candidates.import_sync_max_rows', 200));
        $importId = (string) Str::uuid();

        if ($totalRows === 0) {
            throw ValidationException::withMessages([
                'file' => ['CSV file contains no data rows.'],
            ]);
        }

        if ($totalRows <= $syncMax) {
            $summary = $this->processRows($parsed['rows'], $actorId);

            return [
                'import_id' => $importId,
                'queued' => false,
                'total_rows' => $totalRows,
                'summary' => $summary,
            ];
        }

        $ttl = max(60, (int) config('api.candidates.import_status_cache_ttl_seconds', 86400));
        Cache::put(CacheKeys::candidateImportRows($importId), $parsed['rows'], $ttl);
        $this->putBatchStatus(
            $importId,
            [
                'import_id' => $importId,
                'status' => 'queued',
                'total_rows' => $totalRows,
                'created' => 0,
                'skipped' => 0,
                'errors' => [],
                'actor_id' => $actorId,
                'queued' => true,
            ],
            $ttl
        );

        ImportCandidatesFromCsvJob::dispatch($importId, $actorId);

        return [
            'import_id' => $importId,
            'queued' => true,
            'total_rows' => $totalRows,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function batchStatus(string $importId): ?array
    {
        /** @var array<string, mixed>|null $status */
        $status = Cache::get(CacheKeys::candidateImportBatch($importId));

        return $status;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     *
     * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function processRows(array $rows, int $actorId): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $email = trim((string) ($row['email'] ?? ''));

                if ($email === '') {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Email is required.'];
                    $skipped++;

                    continue;
                }

                if (User::query()->where('email', $email)->exists()) {
                    $errors[] = ['row' => $rowNumber, 'message' => "Duplicate email: {$email}"];
                    $skipped++;

                    continue;
                }

                $profileStatus = trim((string) ($row['profile_status'] ?? 'draft'));

                if ($profileStatus !== '' && !in_array($profileStatus, CandidateUserService::PROFILE_STATUSES, true)) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Invalid profile_status.'];
                    $skipped++;

                    continue;
                }

                $phone = trim((string) ($row['phone'] ?? ''));
                $gender = trim((string) ($row['gender'] ?? ''));
                $maritalStatus = trim((string) ($row['marital_status'] ?? ''));

                $payload = [
                    'email' => $email,
                    'first_name' => trim((string) ($row['first_name'] ?? '')),
                    'last_name' => trim((string) ($row['last_name'] ?? '')),
                    'phone' => $phone !== '' ? $phone : null,
                    'gender' => $gender !== '' ? $gender : null,
                    'marital_status' => $maritalStatus !== '' ? $maritalStatus : null,
                    'profile_status' => $profileStatus !== '' ? $profileStatus : 'draft',
                ];

                $password = trim((string) ($row['password'] ?? ''));

                if ($password !== '') {
                    $payload['password'] = $password;
                }

                $user = $this->candidateUserService->create($payload, $actorId);
                $user->syncRoles(['candidate']);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string|null>>}
     */
    public function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path !== false ? $path : $file->path(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read CSV file.'],
            ]);
        }

        $headerRow = fgetcsv($handle);

        if (!is_array($headerRow)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => ['CSV file is empty.'],
            ]);
        }

        $headers = array_map(static fn(?string $col): string => strtolower(trim((string) $col)), $headerRow);

        $this->assertValidHeaders($headers);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $assoc = [];

            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }

                $assoc[$header] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }

            if ($this->rowIsBlank($assoc)) {
                continue;
            }

            $rows[] = $assoc;
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    public function putBatchStatus(string $importId, array $status, ?int $ttlSeconds = null): void
    {
        $ttl = $ttlSeconds ?? max(60, (int) config('api.candidates.import_status_cache_ttl_seconds', 86400));
        Cache::put(CacheKeys::candidateImportBatch($importId), $status, $ttl);
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertValidHeaders(array $headers): void
    {
        $normalized = array_values(array_filter($headers, static fn(string $h): bool => $h !== ''));
        $allowed = array_merge(self::REQUIRED_HEADERS, self::OPTIONAL_HEADERS);

        foreach (self::REQUIRED_HEADERS as $required) {
            if (!in_array($required, $normalized, true)) {
                throw ValidationException::withMessages([
                    'file' => ["Missing required CSV column: {$required}."],
                ]);
            }
        }

        foreach ($normalized as $header) {
            if (!in_array($header, $allowed, true)) {
                throw ValidationException::withMessages([
                    'file' => ["Unknown CSV column: {$header}."],
                ]);
            }
        }
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
