<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AdminSettingsType;
use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Services\Settings\NotificationConfigResolver;
use App\Services\Settings\PaymentGatewayConfigResolver;
use App\Services\Settings\SearchConfigResolver;
use App\Services\Settings\StorageConfigResolver;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ApplySettingsConfigJob implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly AdminSettingsType $settingType)
    {
        $this->onQueue(QueuePriority::low());
    }

    public function handle(
        PaymentGatewayConfigResolver $paymentResolver,
        NotificationConfigResolver $notificationResolver,
        StorageConfigResolver $storageResolver,
        SearchConfigResolver $searchResolver
    ): void {
        match ($this->settingType) {
            AdminSettingsType::PaymentGateway => $paymentResolver->apply(),
            AdminSettingsType::Notification => $notificationResolver->apply(),
            AdminSettingsType::Storage => $storageResolver->apply(),
            AdminSettingsType::Search => $searchResolver->apply(),
            default => null,
        };

        Cache::forget('settings:runtime-config');
    }
}
