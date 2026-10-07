<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\NotificationSetting;
use App\Support\MailDelivery;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationConfigResolver
{
    public function apply(): void
    {
        if (MailDelivery::usesEnvMail()) {
            return;
        }

        // Boot runs before RefreshDatabase / migrate; skip quietly until the table exists.
        if (!Schema::hasTable('notification_settings')) {
            Config::set('mail.default', 'array');

            return;
        }

        try {
            $settings = NotificationSetting::instance();
        } catch (Throwable $e) {
            Log::warning('notification_settings unavailable; using array mailer', [
                'error' => $e->getMessage(),
            ]);
            Config::set('mail.default', 'array');

            return;
        }

        if (!$settings->email_enabled) {
            Config::set('mail.default', 'array');

            return;
        }

        if (!MailDelivery::smtpIsComplete($settings)) {
            Log::warning('email_enabled but SMTP host is empty; skipping DB mail config');
            Config::set('mail.default', 'array');

            return;
        }

        if (filled($settings->mail_mailer)) {
            Config::set('mail.default', $settings->mail_mailer);
        } else {
            Config::set('mail.default', 'smtp');
        }

        Config::set('mail.mailers.smtp.host', $settings->mail_host);
        Config::set('mail.mailers.smtp.port', $settings->mail_port);
        Config::set('mail.mailers.smtp.username', $settings->mail_username);
        Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);

        if (filled($settings->mail_password)) {
            Config::set('mail.mailers.smtp.password', $settings->mail_password);
        }

        if (filled($settings->mail_from_address)) {
            Config::set('mail.from.address', $settings->mail_from_address);
        }

        if (filled($settings->mail_from_name)) {
            Config::set('mail.from.name', $settings->mail_from_name);
        }
    }
}
