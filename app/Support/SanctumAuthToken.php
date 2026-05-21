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

        return self::issueWithExpiry($user, $name, Carbon::now()->addDays($days));
    }

    public static function issueWithTtlMinutes(User $user, string $name, int $ttlMinutes): string
    {
        $minutes = max(1, $ttlMinutes);

        return self::issueWithExpiry($user, $name, Carbon::now()->addMinutes($minutes));
    }

    public static function issueWithExpiry(User $user, string $name, Carbon $expiresAt): string
    {
        return $user->createToken($name, ['*'], $expiresAt)->plainTextToken;
    }
}
