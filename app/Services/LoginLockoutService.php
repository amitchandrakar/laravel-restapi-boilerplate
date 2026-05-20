<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tracks failed login attempts and temporary account lockouts (cache-backed).
 */
final class LoginLockoutService
{
    public function isLocked(User $user): bool
    {
        return Cache::has($this->lockKeyForUser($user));
    }

    public function isLockedForIdentifier(string $identifier): bool
    {
        $normalized = $this->normalizeIdentifier($identifier);
        if ($normalized === '') {
            return false;
        }

        return Cache::has($this->lockKeyForIdentifier($normalized));
    }

    public function assertNotLocked(User $user): void
    {
        if ($this->isLocked($user)) {
            throw new HttpException(
                423,
                'Account is temporarily locked due to too many failed login attempts. Please try again later.'
            );
        }
    }

    public function recordFailedAttempt(User $user): void
    {
        $this->incrementFailures($this->failureKeyForUser($user), $this->lockKeyForUser($user));
    }

    public function recordFailedAttemptForIdentifier(string $identifier): void
    {
        $normalized = $this->normalizeIdentifier($identifier);
        if ($normalized === '') {
            return;
        }

        $this->incrementFailures($this->failureKeyForIdentifier($normalized), $this->lockKeyForIdentifier($normalized));
    }

    public function clear(User $user): void
    {
        Cache::forget($this->failureKeyForUser($user));
        Cache::forget($this->lockKeyForUser($user));
    }

    private function incrementFailures(string $failureKey, string $lockKey): void
    {
        $maxAttempts = max(1, (int) config('api.auth.lockout.max_attempts', 5));
        $decayMinutes = max(1, (int) config('api.auth.lockout.decay_minutes', 15));
        $decaySeconds = $decayMinutes * 60;

        $attempts = (int) Cache::get($failureKey, 0) + 1;
        Cache::put($failureKey, $attempts, $decaySeconds);

        if ($attempts >= $maxAttempts) {
            Cache::put($lockKey, true, $decaySeconds);
        }
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }

    private function failureKeyForUser(User $user): string
    {
        return 'login_failures:user:' . $user->id;
    }

    private function lockKeyForUser(User $user): string
    {
        return 'login_lockout:user:' . $user->id;
    }

    private function failureKeyForIdentifier(string $normalizedIdentifier): string
    {
        return 'login_failures:identifier:' . hash('sha256', $normalizedIdentifier);
    }

    private function lockKeyForIdentifier(string $normalizedIdentifier): string
    {
        return 'login_lockout:identifier:' . hash('sha256', $normalizedIdentifier);
    }
}
