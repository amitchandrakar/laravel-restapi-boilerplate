<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $otherUser,
        private readonly string $matchUuid,
        private readonly ?int $matchPercentage
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $name = trim($this->otherUser->first_name . ' ' . $this->otherUser->last_name);

        return [
            'kind' => 'new_match',
            'match_uuid' => $this->matchUuid,
            'other_user_uuid' => $this->otherUser->uuid,
            'other_user_name' => $name !== '' ? $name : null,
            'match_percentage' => $this->matchPercentage,
            'message' => $name !== '' ? sprintf('You have a new match with %s.', $name) : 'You have a new match.',
        ];
    }
}
