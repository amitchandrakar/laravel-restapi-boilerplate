<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\EndUserSessionJob;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Jobs\StartUserSessionJob;
use App\Jobs\UpsertUserDeviceLogJob;
use App\Models\User;
use App\Services\UserActionLogService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_flows_dispatch_logging_jobs(): void
    {
        Queue::fake();
        $this->seed(RbacSeeder::class);

        $email = 'queue-log-' . uniqid('', true) . '@example.com';
        $password = 'Password@123';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Queue User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertStatus(200);

        $token = (string) $login->json('data.token');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(200);

        Queue::assertPushed(LogAuditJob::class);
        Queue::assertPushed(LogUserActivityJob::class);
        Queue::assertPushed(UpsertUserDeviceLogJob::class);
        Queue::assertPushed(StartUserSessionJob::class);
        Queue::assertPushed(EndUserSessionJob::class);
    }

    public function test_job_handlers_write_to_all_four_log_tables(): void
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Logger',
            'last_name' => 'User',
            'email' => 'logger-user@example.com',
            'password' => 'Password@123',
            'status' => 'active',
        ]);

        /** @var UserActionLogService $logService */
        $logService = app(UserActionLogService::class);
        $sessionHash = hash('sha256', 'session-token');

        (new LogAuditJob(
            $user->id,
            'users',
            $user->id,
            'test.audit',
            null,
            ['ok' => true],
            '127.0.0.1',
            'tester'
        ))->handle($logService);
        (new LogUserActivityJob($user->id, 'test.activity', 'tests', ['ok' => true], '127.0.0.1'))->handle($logService);
        (new UpsertUserDeviceLogJob($user->id, 'test-device', 'web', 'Test Browser', 'Web'))->handle($logService);
        (new StartUserSessionJob($user->id, $sessionHash, null, '127.0.0.1', 'tester', 'test-device'))->handle(
            $logService
        );
        (new EndUserSessionJob($user->id, $sessionHash))->handle($logService);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'entity_type' => 'users',
            'action' => 'test.audit',
        ]);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'activity_type' => 'test.activity',
        ]);
        $this->assertDatabaseHas('user_device_logs', [
            'user_id' => $user->id,
            'device_id' => 'test-device',
        ]);
        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'session_token_hash' => $sessionHash,
            'is_active' => false,
        ]);
    }
}
