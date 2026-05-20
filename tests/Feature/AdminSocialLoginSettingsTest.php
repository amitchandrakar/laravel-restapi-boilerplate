<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;

it('allows admins to update and fetch social login settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-social@example.com');

    $payload = [
        'googleEnabled' => true,
        'googleEnvironment' => 'live',
        'googleLiveClientId' => 'google-client-id',
        'googleLiveClientSecret' => 'google-client-secret',
        'googleLiveRedirectUrl' => 'https://example.com/auth/google/callback',

        'facebookEnabled' => true,
        'facebookEnvironment' => 'live',
        'facebookLiveClientId' => 'facebook-client-id',
        'facebookLiveClientSecret' => 'facebook-client-secret',
        'facebookLiveRedirectUrl' => 'https://example.com/auth/facebook/callback',

        'instagramEnabled' => true,
        'instagramEnvironment' => 'live',
        'instagramLiveClientId' => 'instagram-client-id',
        'instagramLiveClientSecret' => 'instagram-client-secret',
        'instagramLiveRedirectUrl' => 'https://example.com/auth/instagram/callback',
    ];

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/social-login', $payload)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.googleEnabled', true)
        ->assertJsonPath('data.facebookEnvironment', 'live')
        ->assertJsonPath('data.instagramLiveClientId', 'instagram-client-id');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/social-login')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.googleLiveClientId', 'google-client-id');
});

it('returns forbidden when a candidate updates social login settings', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-social@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/admin/settings/social-login', ['googleEnabled' => true])
        ->assertStatus(403);
});
