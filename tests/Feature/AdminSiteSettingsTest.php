<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;

it('allows admins to update and fetch site settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-site@example.com');

    $payload = [
        'siteName' => 'Kurmi Samaj',
        'logoUrl' => '/logo.png',
        'faviconUrl' => '/favicon.png',
        'allowedCommunitySurnames' => ['Chandrakar', 'Verma', 'Bais'],
        'maintenanceMode' => false,
        'requireProfileApproval' => true,
    ];

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/site', $payload)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siteName', 'Kurmi Samaj')
        ->assertJsonPath('data.allowedCommunitySurnames.0', 'Chandrakar')
        ->assertJsonPath('data.requireProfileApproval', true);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/site')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siteName', 'Kurmi Samaj');
});

it('returns forbidden when a candidate updates site settings', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-site@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/admin/settings/site', ['siteName' => 'Nope'])
        ->assertStatus(403);
});
