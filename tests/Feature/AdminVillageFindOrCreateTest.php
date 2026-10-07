<?php

declare(strict_types=1);

use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('lets admins find or create a village under a city', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-village-create@example.com');
    $cityId = (int) DB::table('cities')->where('is_active', true)->value('id');
    expect($cityId)->toBeGreaterThan(0);

    $uniqueName = 'Admin Village ' . uniqid();

    $create = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/villages', [
            'city_id' => $cityId,
            'name' => $uniqueName,
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', $uniqueName)
        ->assertJsonPath('data.cityId', $cityId);

    $villageId = (int) $create->json('data.id');
    expect($villageId)->toBeGreaterThan(0);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/villages', [
            'city_id' => $cityId,
            'name' => strtolower($uniqueName),
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.id', $villageId)
        ->assertJsonPath('data.cityId', $cityId);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/villages', [
            'city_id' => $cityId,
            'name' => '',
        ])
        ->assertStatus(422);
});

it('rejects village create for users without candidates.edit permission', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(ChhattisgarhMasterGeoSeeder::class);

    $user = $this->createUserWithRole('candidate', 'candidate-village-denied@example.com');
    $user->syncRoles([]);
    $user->syncPermissions([]);
    $cityId = (int) DB::table('cities')->where('is_active', true)->value('id');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/admin/candidates/villages', [
            'city_id' => $cityId,
            'name' => 'Should Fail',
        ])
        ->assertStatus(403);
});
