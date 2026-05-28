<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('lets admins fetch candidate profile details and section progress', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-profile-sections@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-profile-sections@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-details')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $candidate->uuid);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates/' . $candidate->uuid . '/section-progress')
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('lets admins patch candidate profile sections', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-profile-patch@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-profile-patch@example.com');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/basics', [
            'first_name' => 'Patched',
            'last_name' => 'Candidate',
            'gender' => 'male',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.section', 'basics');
});

it('returns not found when profile routes target a non-candidate user', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-profile-team@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'reviewer-profile@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates/' . $reviewer->uuid . '/profile-details')
        ->assertStatus(404);
});
