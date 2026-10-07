<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserPushDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserPushDeviceService
{
    /**
     * Upsert an FCM token for the user. Same token on another user is reassigned.
     *
     * @param  array{fcm_token?: string|null, platform?: string|null, device_id?: string|null, app_version?: string|null}  $payload
     */
    public function register(User $user, array $payload): UserPushDevice
    {
        $token = trim($payload['fcm_token'] ?? '');

        if ($token === '') {
            throw ValidationException::withMessages([
                'fcm_token' => ['A push token is required.'],
            ]);
        }

        $platform = $this->normalizePlatform($payload['platform'] ?? null);
        $deviceId = $this->nullableString($payload['device_id'] ?? null, 255);
        $appVersion = $this->nullableString($payload['app_version'] ?? null, 64);
        $now = now();

        return DB::transaction(function () use (
            $user,
            $token,
            $platform,
            $deviceId,
            $appVersion,
            $now
        ): UserPushDevice {
            $tokenHash = hash('sha256', $token);

            // Token uniquely identifies a device install — move ownership if it was on another account.
            UserPushDevice::query()->where('token_hash', $tokenHash)->where('user_id', '!=', $user->id)->delete();

            /** @var UserPushDevice $device */
            $device = UserPushDevice::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token_hash' => $tokenHash,
                ],
                [
                    'fcm_token' => $token,
                    'platform' => $platform,
                    'device_id' => $deviceId,
                    'app_version' => $appVersion,
                    'last_seen_at' => $now,
                ]
            );

            return $device->fresh() ?? $device;
        });
    }

    public function unregisterToken(User $user, string $fcmToken): void
    {
        $token = trim($fcmToken);

        if ($token === '') {
            return;
        }

        UserPushDevice::query()->where('user_id', $user->id)->where('token_hash', hash('sha256', $token))->delete();
    }

    public function unregisterAll(User $user): void
    {
        UserPushDevice::query()->where('user_id', $user->id)->delete();
    }

    /**
     * @return list<UserPushDevice>
     */
    public function activeDevicesForUser(User $user): array
    {
        $query = UserPushDevice::query()->where('user_id', $user->id);
        $query->getQuery()->orderBy('last_seen_at', 'desc');

        return array_values($query->get()->all());
    }

    private function normalizePlatform(mixed $platform): ?string
    {
        if (!is_string($platform)) {
            return null;
        }

        $p = strtolower(trim($platform));

        return match ($p) {
            'android', 'ios', 'web' => $p,
            'iphone', 'ipad' => 'ios',
            default => $p !== '' ? substr($p, 0, 32) : null,
        };
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, $max);
    }
}
