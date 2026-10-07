<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class AuthUserType
{
    public const CANDIDATE = 'candidate';

    public const TEAM = 'team';

    public static function forUser(User $user): string
    {
        // Roles live on the `web` guard; after auth:sanctum the default driver may be `sanctum`.
        if ($user->hasRole(self::CANDIDATE, 'web')) {
            return self::CANDIDATE;
        }

        return self::TEAM;
    }
}
