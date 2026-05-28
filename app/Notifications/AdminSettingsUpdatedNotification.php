<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\AdminSettingsType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminSettingsUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AdminSettingsType $settingType) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_settings_updated',
            'settingType' => $this->settingType->value,
            'message' => 'Admin settings were updated: ' . $this->settingType->value,
        ];
    }
}
