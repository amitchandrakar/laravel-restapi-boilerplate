<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Standard retry / timeout settings for application queue jobs (app-plan §14).
 */
trait ConfiguresQueueRetries
{
    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function failed(Throwable $exception): void
    {
        Log::error(static::class . ' failed after retries', [
            'job' => static::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
