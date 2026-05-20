<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Services\UserActionLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EndUserSessionJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $userId, private readonly ?string $sessionTokenHash = null) {}

    public function handle(UserActionLogService $logService): void
    {
        $logService->endSession($this->userId, $this->sessionTokenHash);
    }
}
