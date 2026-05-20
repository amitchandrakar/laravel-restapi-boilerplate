<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Services\UserActionLogService;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartUserSessionJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $sessionTokenHash,
        private readonly ?string $refreshTokenHash = null,
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null,
        private readonly ?string $deviceId = null
    ) {
        $this->onQueue(QueuePriority::high());
    }

    public function handle(UserActionLogService $logService): void
    {
        $logService->startSession(
            $this->userId,
            $this->sessionTokenHash,
            $this->refreshTokenHash,
            $this->ipAddress,
            $this->userAgent,
            $this->deviceId
        );
    }
}
