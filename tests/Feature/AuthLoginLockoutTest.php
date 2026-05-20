<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Cache;

const AUTH_LOGIN_LOCKOUT_PW = 'Password@lock1';
beforeEach(function () {
    $this->seed(RbacSeeder::class);
    config([
        'api.auth.lockout.max_attempts' => 5,
        'api.auth.lockout.decay_minutes' => 15,
    ]);
});

it('locks accounts after five consecutive failed password attempts', function () {
    $email = 'lockout-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Lockout Test',
        'email' => $email,
        'password' => AUTH_LOGIN_LOCKOUT_PW,
        'password_confirmation' => AUTH_LOGIN_LOCKOUT_PW,
    ])->assertStatus(201);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    $this->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => AUTH_LOGIN_LOCKOUT_PW,
    ])
        ->assertStatus(423)
        ->assertJsonPath('success', false);
});

it('clears failed-login counters after a successful password check', function () {
    $email = 'lockout-clear-' . uniqid('', true) . '@example.com';

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Lockout Clear',
        'email' => $email,
        'password' => AUTH_LOGIN_LOCKOUT_PW,
        'password_confirmation' => AUTH_LOGIN_LOCKOUT_PW,
    ])->assertStatus(201);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    $this->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => AUTH_LOGIN_LOCKOUT_PW,
    ])->assertStatus(200);

    Cache::flush();

    $this->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => AUTH_LOGIN_LOCKOUT_PW,
    ])->assertStatus(200);
});
