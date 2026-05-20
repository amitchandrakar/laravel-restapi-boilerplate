<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Package $package) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New package created')
            ->line('A new package was created: ' . $this->package->name . ' (' . $this->package->code . ').');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_created',
            'package_id' => $this->package->id,
            'package_uuid' => $this->package->uuid,
            'name' => $this->package->name,
            'code' => $this->package->code,
            'message' => 'A new package has been created.',
        ];
    }
}
