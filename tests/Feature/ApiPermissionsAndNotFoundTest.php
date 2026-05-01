<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApiResponseBuilder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiPermissionsAndNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_payment_uuid_returns_not_found_envelope(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'api-404-payment@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/payments/' . (string) Str::uuid())
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
            ->assertJsonPath('message', 'Payment not found');
    }

    public function test_unknown_role_uuid_returns_not_found_envelope(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'api-404-role@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/roles/' . (string) Str::uuid() . '/permissions')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
            ->assertJsonPath('message', 'Role not found');
    }

    public function test_unknown_package_id_returns_not_found_envelope(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'api-404-package@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/packages/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
            ->assertJsonPath('message', 'Package not found');
    }

    public function test_unknown_user_id_returns_not_found_envelope(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'api-404-user@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_NOT_FOUND)
            ->assertJsonPath('message', 'User not found');
    }

    public function test_candidate_gets_forbidden_on_dashboard_stats_with_consistent_envelope(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'api-403-dashboard@example.com');

        $response = $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');
        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_FORBIDDEN);
        $this->assertStringContainsStringIgnoringCase('permission', (string) $response->json('message'));
    }

    public function test_candidate_gets_forbidden_on_users_index(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'api-403-users@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiResponseBuilder::ERROR_FORBIDDEN);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
