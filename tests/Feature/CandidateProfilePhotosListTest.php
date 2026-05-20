<?php

declare(strict_types=1);
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('returns authenticated candidates their profile photo gallery JSON', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photos-list@example.com');
    $uuid = (string) Str::uuid();
    DB::table('user_images')->insert([
        'uuid' => $uuid,
        'user_id' => $candidate->id,
        'image_type' => 'profile',
        'image_storage_path' => null,
        'image_url' => 'https://example.com/md.jpg',
        'thumbnail_url' => 'https://example.com/sm.jpg',
        'icon_url' => null,
        'is_profile_photo' => true,
        'sort_order' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/auth/candidate/' . $candidate->uuid . '/photos')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.url', 'https://example.com/md.jpg')
        ->assertJsonPath('data.0.thumbnailUrl', 'https://example.com/sm.jpg')
        ->assertJsonPath('data.0.isProfilePhoto', true)
        ->assertJsonPath('data.0.sortOrder', 0);
});

it('returns unauthorized when guests list candidate photo galleries', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photos-list-guest@example.com');

    $this->getJson('/api/v1/auth/candidate/' . $candidate->uuid . '/photos')->assertStatus(401);
});

it('returns forbidden when non-candidate roles list candidate photo galleries', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'photos-list-admin@example.com');
    $candidate = $this->createUserWithRole('candidate', 'photos-list-target@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/auth/candidate/' . $candidate->uuid . '/photos')
        ->assertStatus(403);
});

it('returns forbidden when a candidate lists another member\'s gallery', function () {
    $this->seed(RbacSeeder::class);
    $a = $this->createUserWithRole('candidate', 'photos-list-a@example.com');
    $b = $this->createUserWithRole('candidate', 'photos-list-b@example.com');

    $this->actingAs($a, 'sanctum')
        ->getJson('/api/v1/auth/candidate/' . $b->uuid . '/photos')
        ->assertStatus(403);
});
