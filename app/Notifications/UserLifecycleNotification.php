<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class UserLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $subjectUser, private readonly string $action) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_lifecycle',
            'action' => $this->action,
            'user_id' => $this->subjectUser->id,
            'role_id' => $this->subjectUser->role_id,
            'role' => data_get($this->subjectUser, 'primaryRole.name'),
            'email' => $this->subjectUser->email,
            'message' => sprintf(
                'User %s: %s (role_id=%s)',
                $this->action,
                $this->subjectUser->email,
                (string) $this->subjectUser->role_id
            ),
        ];
    }
}
