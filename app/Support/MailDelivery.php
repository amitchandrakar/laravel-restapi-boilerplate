<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class MailDelivery
{
    /**
     * Only local uses .env MAIL_* for notification email.
     * Staging, production, testing, and any other env use notification_settings.
     */
    public static function usesEnvMail(): bool
    {
        return app()->environment('local');
    }

    public static function shouldSendMail(): bool
    {
        if (self::usesEnvMail()) {
            return true;
        }

        if (!self::settingsTableReady()) {
            return false;
        }

        try {
            $settings = NotificationSetting::instance();
        } catch (Throwable) {
            return false;
        }

        return $settings->email_enabled && self::smtpIsComplete($settings);
    }

    public static function smtpIsComplete(?NotificationSetting $settings = null): bool
    {
        if ($settings === null && !self::settingsTableReady()) {
            return false;
        }

        try {
            $settings ??= NotificationSetting::instance();
        } catch (Throwable) {
            return false;
        }

        return filled($settings->mail_host);
    }

    private static function settingsTableReady(): bool
    {
        try {
            return Schema::hasTable('notification_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
