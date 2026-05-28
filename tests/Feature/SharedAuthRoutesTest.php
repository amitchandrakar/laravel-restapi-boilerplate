<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('returns userType candidate on shared login and me for a candidate', function (): void {
    /** @var User $user */
    $user = User::query()->create([
        'first_name' => 'Shared',
        'last_name' => 'Candidate',
        'email' => 'shared-candidate-' . uniqid('', true) . '@example.com',
        'password' => 'Password@shared1',
        'status' => 'active',
    ]);
    $user->assignRole('candidate');

    $login = $this->postJson('/api/v1/auth/login', [
        'username' => $user->email,
        'password' => 'Password@shared1',
    ]);

    $login
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.userType', 'candidate')
        ->assertJsonPath('data.user.userType', 'candidate');

    $token = (string) $login->json('data.token');

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('data.userType', 'candidate')
        ->assertJsonPath('data.user.userType', 'candidate')
        ->assertJsonPath('data.user.email', $user->email);
});

it('returns userType team on shared login and me for staff', function (): void {
    $email = 'shared-admin-' . uniqid('', true) . '@example.com';
    $user = $this->createUserWithRole('admin', $email, 'Staff');

    $login = $this->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => 'Password@123',
    ]);

    $login->assertStatus(200)->assertJsonPath('data.userType', 'team')->assertJsonPath('data.user.userType', 'team');

    $token = (string) $login->json('data.token');

    $me = $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('data.userType', 'team')
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonStructure(['data' => ['permissions']]);

    expect($me->json('data.permissions'))->not->toBeEmpty();
});
