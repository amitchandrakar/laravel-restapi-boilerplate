<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\UserActionLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpsertUserDeviceLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $deviceId,
        private readonly ?string $deviceType = null,
        private readonly ?string $deviceName = null,
        private readonly ?string $osName = null,
        private readonly ?string $osVersion = null,
        private readonly ?string $appVersion = null,
        private readonly ?string $pushToken = null
    ) {}

    public function handle(UserActionLogService $logService): void
    {
        $logService->upsertDeviceLog(
            $this->userId,
            $this->deviceId,
            $this->deviceType,
            $this->deviceName,
            $this->osName,
            $this->osVersion,
            $this->appVersion,
            $this->pushToken
        );
    }
}
