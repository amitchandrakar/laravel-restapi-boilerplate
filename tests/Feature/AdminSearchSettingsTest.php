<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;

it('allows admins to update search settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-search@example.com');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/search', [
            'isEnabled' => false,
            'candidateIndexName' => 'candidates_dev',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.candidateIndexName', 'candidates_dev');
});
