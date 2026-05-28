<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Config;

class NotificationConfigResolver
{
    public function apply(): void
    {
        $settings = NotificationSetting::instance();

        if (!$settings->email_enabled) {
            return;
        }

        if (filled($settings->mail_mailer)) {
            Config::set('mail.default', $settings->mail_mailer);
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
