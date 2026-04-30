<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\UserActionLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogUserActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        private readonly int $userId,
        private readonly string $activityType,
        private readonly ?string $activitySource = null,
        private readonly ?array $metadata = null,
        private readonly ?string $ipAddress = null
    ) {}

    public function handle(UserActionLogService $logService): void
    {
        $logService->logActivity(
            $this->userId,
            $this->activityType,
            $this->activitySource,
            $this->metadata,
            $this->ipAddress
        );
    }
}
