<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Builds admin candidate CSV export rows from list filters.
 */
class CandidateCsvExportService
{
    /** @var list<string> */
    public const HEADERS = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'marital_status',
        'height',
        'weight',
        'blood_group',
        'body_type',
        'profile_status',
        'status',
        'is_featured',
        'published_at',
        'created_at',
        'updated_at',
    ];

    public function __construct(private readonly CandidateUserService $candidateUserService) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Collection<int, User>
     */
    public function candidatesForExport(array $filters): Collection
    {
        $maxRows = max(1, (int) config('api.candidates.export_max_rows', 5000));
        $count = $this->candidateUserService->countForList($filters);

        if ($count > $maxRows) {
            throw ValidationException::withMessages([
                'export' => ["Too many candidates ({$count}). Narrow filters or export at most {$maxRows} rows."],
            ]);
        }

        return $this->candidateUserService->listForExport($filters);
    }

    /**
     * @return list<list<string|null>>
     */
    public function rowsForCsv(Collection $candidates): array
    {
        $rows = [];

        foreach ($candidates as $user) {
            if (!($user instanceof User)) {
                continue;
            }
            $rows[] = [
                $user->uuid ?? '',
                $user->first_name ?? '',
                $user->last_name ?? '',
                $user->email ?? '',
                $user->phone ?? '',
                $user->gender ?? '',
                $user->marital_status ?? '',
                $user->height !== null ? (string) $user->height : null,
                $user->weight !== null ? (string) $user->weight : null,
                $user->blood_group ?? '',
                $user->body_type ?? '',
                $user->profile_status ?? '',
                $user->status ?? '',
                $user->is_featured ? '1' : '0',
                $this->formatDateTimeColumn($user->published_at),
                $this->formatDateTimeColumn($user->created_at),
                $this->formatDateTimeColumn($user->updated_at),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filename(array $filters): string
    {
        $bucket = (string) ($filters['bucket'] ?? 'all');
        $date = now()->format('Y-m-d');

        return "candidates-{$bucket}-{$date}.csv";
    }

    private function formatDateTimeColumn(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        return (string) $value;
    }
}
