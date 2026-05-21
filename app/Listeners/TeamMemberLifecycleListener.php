<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TeamMemberLifecycleEvent;
use App\Models\User;
use App\Notifications\TeamMemberNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TeamMemberLifecycleListener implements ShouldQueue
{
    public function handle(TeamMemberLifecycleEvent $event): void
    {
        $recipients = User::query()
            ->teamUsers()
            ->where('status', 'active')
            ->get()
            ->filter(static function (User $recipient) use ($event): bool {
                if ((int) $recipient->id === (int) $event->teamMember->id) {
                    return false;
                }

                return $recipient->can('admin.teams.view');
            });

        if ($recipients->isEmpty()) {
            Log::warning('TeamMemberLifecycleListener: no recipients for team notification', [
                'team_member_id' => $event->teamMember->id,
                'action' => $event->action,
            ]);

            return;
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new TeamMemberNotification($event->teamMember, $event->action, $event->actorUserId));
        }
    }
}
