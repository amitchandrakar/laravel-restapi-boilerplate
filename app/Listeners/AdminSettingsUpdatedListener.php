<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdminSettingsUpdatedEvent;
use App\Models\User;
use App\Notifications\AdminSettingsUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AdminSettingsUpdatedListener implements ShouldQueue
{
    public function handle(AdminSettingsUpdatedEvent $event): void
    {
        Log::info('Admin settings updated', [
            'setting_type' => $event->settingType->value,
            'actor_user_id' => $event->actorUserId,
        ]);

        $admins = User::query()->role('admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new AdminSettingsUpdatedNotification($event->settingType));
    }
}
