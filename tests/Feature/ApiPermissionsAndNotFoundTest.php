<?php

declare(strict_types=1);
use App\Models\User;
use App\Support\ApiResponseBuilder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;

it('returns not found for an unknown payment UUID', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'api-404-payment@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/payments/' . (string) Str::uuid())
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
        ->assertJsonPath('message', 'Payment not found');
});

it('returns not found for an unknown role UUID', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'api-404-role@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/roles/' . (string) Str::uuid() . '/permissions')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
        ->assertJsonPath('message', 'Role not found');
});

it('returns not found for an unknown package numeric id', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'api-404-package@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/packages/999999')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
        ->assertJsonPath('message', 'Package not found');
});

it('returns not found for an unknown user id', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'api-404-user@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/users/999999')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
        ->assertJsonPath('message', 'User not found');
});

it('returns forbidden with the standard envelope when a candidate opens dashboard stats', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'api-403-dashboard@example.com');

    $response = $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');
    $response
        ->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_FORBIDDEN);
    $this->assertStringContainsStringIgnoringCase('permission', (string) $response->json('message'));
});

it('returns forbidden when a candidate lists users', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'api-403-users@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/users')
        ->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_FORBIDDEN);
});
