<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\UserActionLogService;
use App\Support\ApiResponseBuilder;
use App\Support\SanctumPlainTokenHasher;
use Database\Seeders\RbacSeeder;

it('authorizes `/me` after signup while a freshly started tracked session is active', function () {
    $this->seed(RbacSeeder::class);

    $email = 'tracked-reg-' . uniqid('', true) . '@example.com';
    $password = 'Password@tracked1';

    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Tracked Reg',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);
    $register->assertStatus(201)->assertJsonPath('success', true);
    $token = (string) $register->json('data.token');
    expect($register->json('data.session_token_hash'))->toBeString();

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(200)->assertJsonPath('success', true);
});

it('returns HTTP 403 on `/me` when the tracked session ended but the PAT remains valid', function () {
    $this->seed(RbacSeeder::class);

    $email = 'tracked-end-' . uniqid('', true) . '@example.com';
    $password = 'Password@tracked2';

    $this->postJson('/api/v1/app/auth/register', [
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
    expect($user)->not->toBeNull();

    app(UserActionLogService::class)->endSession((int) $user->id, $hash);

    $ended = $this->withToken($token)->getJson('/api/v1/auth/me');
    $ended
        ->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_SESSION_INVALID);
});

it('refreshes the opaque session token hash without breaking subsequent `/me` calls', function () {
    $this->seed(RbacSeeder::class);

    $email = 'tracked-refresh-' . uniqid('', true) . '@example.com';
    $password = 'Password@tracked3';

    $this->postJson('/api/v1/app/auth/register', [
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
    expect(strlen($secondHash))->toBe(64);

    $this->withToken($secondToken)->getJson('/api/v1/auth/me')->assertStatus(200);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect(app(UserActionLogService::class)->hasActiveUserSession((int) $user->id, $firstHash))->toBeFalse(
        'Old session hash should be inactive after refresh'
    );
    expect(app(UserActionLogService::class)->hasActiveUserSession((int) $user->id, $secondHash))->toBeTrue();
});

it('hashes issued login tokens with the same algorithm as plain-text PAT inspection', function () {
    $this->seed(RbacSeeder::class);

    $email = 'tracked-hash-' . uniqid('', true) . '@example.com';
    $password = 'Password@tracked4';

    $this->postJson('/api/v1/app/auth/register', [
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
    expect($login->json('data.session_token_hash'))->toBe($expected);
});
