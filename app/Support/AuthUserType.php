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
        if ($user->hasRole(self::CANDIDATE)) {
            return self::CANDIDATE;
        }

        return self::TEAM;
    }
}
