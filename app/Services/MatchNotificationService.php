<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\NewMatchNotification;

/**
 * Dispatches in-app notifications when a mutual match row exists.
 * Call this wherever new rows are inserted into `matches` (batch jobs, admin tools, etc.).
 */
class MatchNotificationService
{
    /**
     * Notify both users about an active match (symmetric: each sees the other as `other_user`).
     */
    public function notifyBothUsersOfMatch(User $user, User $matchedUser, string $matchUuid, ?int $matchPercentage): void
    {
        $user->notify(new NewMatchNotification($matchedUser, $matchUuid, $matchPercentage));
        $matchedUser->notify(new NewMatchNotification($user, $matchUuid, $matchPercentage));
    }
}
