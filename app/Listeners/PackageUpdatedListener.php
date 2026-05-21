<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PackageUpdatedEvent;
use App\Models\User;
use App\Notifications\PackageUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class PackageUpdatedListener implements ShouldQueue
{
    public function handle(PackageUpdatedEvent $event): void
    {
        $admins = User::query()->role('admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new PackageUpdatedNotification($event->package));
    }
}
