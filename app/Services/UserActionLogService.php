<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserActionLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logAudit(
        ?int $actorUserId,
        string $entityType,
        int $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        DB::table('audit_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values_json' => $oldValues !== null ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
            'new_values_json' => $newValues !== null ? json_encode($newValues, JSON_THROW_ON_ERROR) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function logActivity(
        int $userId,
        string $activityType,
        ?string $activitySource = null,
        ?array $metadata = null,
        ?string $ipAddress = null
    ): void {
        DB::table('user_activity_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_source' => $activitySource,
            'metadata_json' => $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    public function upsertDeviceLog(
        int $userId,
        string $deviceId,
        ?string $deviceType = null,
        ?string $deviceName = null,
        ?string $osName = null,
        ?string $osVersion = null,
        ?string $appVersion = null,
        ?string $pushToken = null
    ): void {
        DB::table('user_device_logs')->updateOrInsert(
            ['user_id' => $userId, 'device_id' => $deviceId],
            [
                'uuid' => (string) Str::uuid(),
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'os_name' => $osName,
                'os_version' => $osVersion,
                'app_version' => $appVersion,
                'push_token' => $pushToken,
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function startSession(
        int $userId,
        string $sessionTokenHash,
        ?string $refreshTokenHash = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceId = null
    ): void {
        DB::table('user_sessions')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'session_token_hash' => $sessionTokenHash,
            'refresh_token_hash' => $refreshTokenHash,
            'login_at' => now(),
            'expires_at' => now()->addDays(30),
            'logout_at' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_id' => $deviceId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function endSession(int $userId, ?string $sessionTokenHash = null): void
    {
        $query = DB::table('user_sessions')->where('user_id', $userId)->where('is_active', true);

        if ($sessionTokenHash !== null && $sessionTokenHash !== '') {
            $query->where('session_token_hash', $sessionTokenHash);
        }

        $query->update([
            'is_active' => false,
            'logout_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function hasActiveUserSession(int $userId, string $sessionTokenHash): bool
    {
        $sessionTokenHash = trim($sessionTokenHash);

        if ($sessionTokenHash === '') {
            return false;
        }

        return DB::table('user_sessions')
            ->where('user_id', $userId)
            ->where('session_token_hash', $sessionTokenHash)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
