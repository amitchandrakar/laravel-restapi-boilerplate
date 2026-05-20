<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Issues Sanctum personal access tokens with a consistent expiry policy.
 */
final class SanctumAuthToken
{
    public static function issue(User $user, string $name = 'auth-token'): string
    {
        $days = max(1, (int) config('api.auth.token_expiry_days', 30));
        $expiresAt = Carbon::now()->addDays($days);

        return $user->createToken($name, ['*'], $expiresAt)->plainTextToken;
    }
}
