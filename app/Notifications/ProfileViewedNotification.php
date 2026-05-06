<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class ProfileViewedNotification extends Notification
{
    public function __construct(private readonly User $viewer, private readonly string $source) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $name = trim($this->viewer->first_name . ' ' . $this->viewer->last_name);

        return [
            'kind' => 'profile_viewed',
            'viewer_user_uuid' => $this->viewer->uuid,
            'viewer_name' => $name !== '' ? $name : null,
            'source' => $this->source,
            'message' => $name !== '' ? sprintf('%s viewed your profile.', $name) : 'Someone viewed your profile.',
        ];
    }
}
