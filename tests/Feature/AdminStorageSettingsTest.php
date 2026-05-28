<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;

it('allows admins to update storage settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-storage@example.com');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/storage', [
            'isEnabled' => true,
            'bucket' => 'community-connect',
            'region' => 'ap-south-1',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.bucket', 'community-connect');
});
