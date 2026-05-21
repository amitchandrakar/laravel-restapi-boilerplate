<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Services\CandidateCsvImportService;
use App\Support\CacheKeys;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportCandidatesFromCsvJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $importId, private readonly int $actorId)
    {
        $this->onQueue(QueuePriority::low());
    }

    public function handle(CandidateCsvImportService $importService): void
    {
        $ttl = max(60, (int) config('api.candidates.import_status_cache_ttl_seconds', 86400));
        $rowsKey = CacheKeys::candidateImportRows($this->importId);

        /** @var list<array<string, string|null>>|null $rows */
        $rows = Cache::get($rowsKey);

        if ($rows === null) {
            Log::error('admin.candidates.import_rows_missing', ['import_id' => $this->importId]);

            $importService->putBatchStatus(
                $this->importId,
                [
                    'import_id' => $this->importId,
                    'status' => 'failed',
                    'message' => 'Import data expired or not found.',
                ],
                $ttl
            );

            return;
        }

        $importService->putBatchStatus(
            $this->importId,
            [
                'import_id' => $this->importId,
                'status' => 'processing',
                'total_rows' => count($rows),
                'actor_id' => $this->actorId,
                'queued' => true,
            ],
            $ttl
        );

        try {
            $summary = $importService->processRows($rows, $this->actorId);
            $importService->putBatchStatus(
                $this->importId,
                [
                    'import_id' => $this->importId,
                    'status' => 'completed',
                    'queued' => true,
                    'total_rows' => count($rows),
                    'created' => $summary['created'],
                    'skipped' => $summary['skipped'],
                    'errors' => $summary['errors'],
                    'finished_at' => now()->toIso8601String(),
                ],
                $ttl
            );
        } catch (\Throwable $e) {
            Log::error('admin.candidates.import_job_failed', [
                'import_id' => $this->importId,
                'message' => $e->getMessage(),
            ]);

            $importService->putBatchStatus(
                $this->importId,
                [
                    'import_id' => $this->importId,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ],
                $ttl
            );

            throw $e;
        } finally {
            Cache::forget($rowsKey);
        }
    }
}
