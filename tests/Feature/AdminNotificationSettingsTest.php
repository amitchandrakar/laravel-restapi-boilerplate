<?php

declare(strict_types=1);

use App\Models\NotificationSetting;
use Database\Seeders\RbacSeeder;

it('masks notification secrets on fetch', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-notifications@example.com');

    NotificationSetting::instance()->update(['mail_password' => 'smtp-secret']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/notifications')
        ->assertStatus(200)
        ->assertJsonPath('data.hasMailPassword', true)
        ->assertJsonMissingPath('data.mailPassword');
});

it('returns forbidden for candidate on notification settings', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-notifications@example.com');

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/settings/notifications')->assertStatus(403);
});
