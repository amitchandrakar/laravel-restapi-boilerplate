<?php

declare(strict_types=1);

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;

class SettingsRuntimeBootstrap
{
    public function __construct(
        private readonly PaymentGatewayConfigResolver $paymentGatewayConfigResolver,
        private readonly NotificationConfigResolver $notificationConfigResolver,
        private readonly StorageConfigResolver $storageConfigResolver,
        private readonly SearchConfigResolver $searchConfigResolver
    ) {}

    public function apply(): void
    {
        Cache::remember('settings:runtime-config', 300, function (): bool {
            $this->paymentGatewayConfigResolver->apply();
            $this->notificationConfigResolver->apply();
            $this->storageConfigResolver->apply();
            $this->searchConfigResolver->apply();

            return true;
        });
    }
}
