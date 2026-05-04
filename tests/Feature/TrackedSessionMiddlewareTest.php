<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserActionLogService;
use App\Support\ApiResponseBuilder;
use App\Support\SanctumPlainTokenHasher;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackedSessionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_succeeds_after_register_when_session_started(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'tracked-reg-' . uniqid('', true) . '@example.com';
        $password = 'Password@tracked1';

        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Tracked Reg',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
        $register->assertStatus(201)->assertJsonPath('success', true);
        $token = (string) $register->json('data.token');
        $this->assertIsString($register->json('data.session_token_hash'));

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_me_returns_403_when_tracked_session_ended_but_pat_still_valid(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'tracked-end-' . uniqid('', true) . '@example.com';
        $password = 'Password@tracked2';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Tracked End',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $token = (string) $login->json('data.token');
        $hash = (string) $login->json('data.session_token_hash');

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);

        app(UserActionLogService::class)->endSession((int) $user->id, $hash);

        $ended = $this->withToken($token)->getJson('/api/v1/auth/me');
        $ended
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_SESSION_INVALID);
    }

    public function test_refresh_returns_new_session_token_hash_and_me_still_works(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'tracked-refresh-' . uniqid('', true) . '@example.com';
        $password = 'Password@tracked3';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Tracked Refresh',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $firstToken = (string) $login->json('data.token');
        $firstHash = (string) $login->json('data.session_token_hash');

        $refresh = $this->withToken($firstToken)->postJson('/api/v1/auth/refresh');
        $refresh->assertStatus(200)->assertJsonPath('success', true);
        $secondToken = (string) $refresh->json('data.token');
        $secondHash = (string) $refresh->json('data.session_token_hash');
        $this->assertNotSame($firstToken, $secondToken);
        $this->assertNotSame($firstHash, $secondHash);
        $this->assertSame(64, strlen($secondHash));

        $this->withToken($secondToken)->getJson('/api/v1/auth/me')->assertStatus(200);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertFalse(
            app(UserActionLogService::class)->hasActiveUserSession((int) $user->id, $firstHash),
            'Old session hash should be inactive after refresh'
        );
        $this->assertTrue(app(UserActionLogService::class)->hasActiveUserSession((int) $user->id, $secondHash));
    }

    public function test_login_still_matches_plain_token_hashing_contract(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'tracked-hash-' . uniqid('', true) . '@example.com';
        $password = 'Password@tracked4';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Hash Contract',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $plain = (string) $login->json('data.token');
        $expected = SanctumPlainTokenHasher::hashPlainTextToken($plain);
        $this->assertSame($expected, $login->json('data.session_token_hash'));
    }
}
