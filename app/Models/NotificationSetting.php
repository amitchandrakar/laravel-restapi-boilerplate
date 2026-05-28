<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class NotificationSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'notification_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
            'twilio_auth_token' => 'encrypted',
            'fcm_server_key' => 'encrypted',
            'fcm_client_key' => 'encrypted',
        ];
    }
}
