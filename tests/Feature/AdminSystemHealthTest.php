<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

it('allows admins to fetch system health', function () {
    $admin = $this->createUserWithRole('admin', 'admin-health@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/system-health')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'status',
                'timestamp',
                'services' => ['database', 'cache', 'queue', 'storage', 'object_storage', 'search'],
            ],
        ]);
});

it('returns forbidden when a candidate fetches system health', function () {
    $candidate = $this->createUserWithRole('candidate', 'candidate-health@example.com');

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/system-health')->assertStatus(403);
});
