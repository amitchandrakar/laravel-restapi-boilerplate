<?php

declare(strict_types=1);

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;

class SettingsRuntimeBootstrap
{
    public const CACHE_KEY = 'settings:runtime-config:v2';

    private static bool $appliedInProcess = false;

    public function __construct(
        private readonly NotificationConfigResolver $notificationConfigResolver,
        private readonly StorageConfigResolver $storageConfigResolver,
        private readonly SearchConfigResolver $searchConfigResolver
    ) {}

    /**
     * Apply DB-backed config to the runtime once per PHP worker process.
     * Notification mail is also applied on every AppServiceProvider boot (local keeps .env;
     * staging/production use notification_settings). Use settings:bootstrap-runtime on deploy
     * or ApplySettingsConfigJob after admin settings changes for storage/search/mail refresh.
     */
    public function apply(): void
    {
        if (self::$appliedInProcess) {
            return;
        }

        $this->applyResolvers();
        Cache::put(self::CACHE_KEY, true, 3600);

        self::$appliedInProcess = true;
    }

    public function forceRefresh(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('settings:runtime-config');
        self::resetProcessState();
        $this->apply();
    }

    public static function resetProcessState(): void
    {
        self::$appliedInProcess = false;
    }

    private function applyResolvers(): void
    {
        $this->notificationConfigResolver->apply();
        $this->storageConfigResolver->apply();
        $this->searchConfigResolver->apply();
    }
}
