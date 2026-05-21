<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PackageUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Package $package) {}

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
            'type' => 'package_updated',
            'package_id' => $this->package->id,
            'package_uuid' => $this->package->uuid,
            'name' => $this->package->name,
            'code' => $this->package->code,
            'message' => 'A package has been updated.',
        ];
    }
}
