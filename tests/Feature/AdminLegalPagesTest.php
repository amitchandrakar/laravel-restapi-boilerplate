<?php

declare(strict_types=1);

use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RbacSeeder;

it('lists and updates legal pages by slug', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-legal@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/legal-pages')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['slug' => 'terms']);

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/legal-pages/terms', [
            'title' => 'Terms of Use',
            'body' => '<p>Updated terms.</p>',
            'isPublished' => true,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Terms of Use')
        ->assertJsonPath('data.isPublished', true);
});

it('returns forbidden when candidate updates legal pages', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-legal@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/admin/settings/legal-pages/terms', ['title' => 'Nope'])
        ->assertStatus(403);
});
