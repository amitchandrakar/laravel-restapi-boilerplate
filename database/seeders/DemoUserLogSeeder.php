<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoUserLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('users')->select('id')->get();
        $now = now();

        foreach ($users as $user) {
            $userId = (int) $user->id;
            $deviceId = 'demo-web-' . $userId;

            if (
                DB::table('user_activity_logs')
                    ->where('user_id', $userId)
                    ->where('activity_type', 'seed.login')
                    ->doesntExist()
            ) {
                DB::table('user_activity_logs')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'activity_type' => 'seed.login',
                    'activity_source' => 'seeder',
                    'metadata_json' => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
                    'ip_address' => '127.0.0.1',
                    'created_at' => $now,
                ]);
            }

            DB::table('user_device_logs')->updateOrInsert(
                ['user_id' => $userId, 'device_id' => $deviceId],
                [
                    'uuid' => (string) Str::uuid(),
                    'device_type' => 'web',
                    'device_name' => 'Demo Browser',
                    'os_name' => 'Web',
                    'os_version' => '1.0',
                    'app_version' => '1.0.0',
                    'push_token' => null,
                    'last_seen_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if (
                DB::table('user_sessions')
                    ->where('user_id', $userId)
                    ->where('session_token_hash', hash('sha256', 'seed-session-' . $userId))
                    ->doesntExist()
            ) {
                DB::table('user_sessions')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'session_token_hash' => hash('sha256', 'seed-session-' . $userId),
                    'refresh_token_hash' => hash('sha256', 'seed-refresh-' . $userId),
                    'login_at' => $now,
                    'expires_at' => $now->copy()->addDays(30),
                    'logout_at' => null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder Demo Agent',
                    'device_id' => $deviceId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (
                DB::table('audit_logs')
                    ->where('actor_user_id', $userId)
                    ->where('action', 'seed.profile.view')
                    ->doesntExist()
            ) {
                DB::table('audit_logs')->insert([
                    'uuid' => (string) Str::uuid(),
                    'actor_user_id' => $userId,
                    'entity_type' => 'users',
                    'entity_id' => $userId,
                    'action' => 'seed.profile.view',
                    'old_values_json' => null,
                    'new_values_json' => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder Demo Agent',
                    'created_at' => $now,
                ]);
            }
        }
    }
}
