<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;

it('allows admins to fetch redis settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-redis@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/redis')
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});
