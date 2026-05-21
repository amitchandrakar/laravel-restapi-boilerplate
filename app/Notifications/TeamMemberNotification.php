<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TeamMemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $teamMember,
        private readonly string $action,
        private readonly ?int $actorUserId = null
    ) {}

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
            'type' => 'team_member_lifecycle',
            'action' => $this->action,
            'team_member_id' => $this->teamMember->id,
            'team_member_uuid' => $this->teamMember->uuid,
            'email' => $this->teamMember->email,
            'role' => data_get($this->teamMember, 'primaryRole.name'),
            'actor_user_id' => $this->actorUserId,
            'message' => sprintf(
                'Team member %s: %s %s',
                $this->action,
                trim(($this->teamMember->first_name ?? '') . ' ' . ($this->teamMember->last_name ?? '')),
                (string) $this->teamMember->email
            ),
        ];
    }
}
