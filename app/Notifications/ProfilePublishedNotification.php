<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ProfilePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $publishedAtStr = null;

        if ($this->user->published_at !== null) {
            $publishedAtStr = Carbon::parse($this->user->published_at)->toIso8601String();
        }

        return [
            'kind' => 'profile_published',
            'user_uuid' => $this->user->uuid,
            'published_at' => $publishedAtStr,
            'message' => 'Your profile has been published.',
        ];
    }
}
