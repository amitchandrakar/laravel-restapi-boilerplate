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

class LogAuditJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function __construct(
        private readonly ?int $actorUserId,
        private readonly string $entityType,
        private readonly int $entityId,
        private readonly string $action,
        private readonly ?array $oldValues = null,
        private readonly ?array $newValues = null,
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null
    ) {
        $this->onQueue(QueuePriority::default());
    }

    public function handle(UserActionLogService $logService): void
    {
        $logService->logAudit(
            $this->actorUserId,
            $this->entityType,
            $this->entityId,
            $this->action,
            $this->oldValues,
            $this->newValues,
            $this->ipAddress,
            $this->userAgent
        );
    }
}
