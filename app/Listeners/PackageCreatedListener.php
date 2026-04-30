<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PackageCreatedEvent;
use App\Models\User;
use App\Notifications\PackageCreatedNotification;
use Illuminate\Support\Facades\Notification;

class PackageCreatedListener
{
    public function handle(PackageCreatedEvent $event): void
    {
        $admins = User::query()->role('admin')->get();
        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new PackageCreatedNotification($event->package));
    }
}
