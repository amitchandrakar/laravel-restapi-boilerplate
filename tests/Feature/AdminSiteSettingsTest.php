<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_fetch_site_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-site@example.com');

        $payload = [
            'siteName' => 'Kurmi Samaj',
            'logoUrl' => '/logo.png',
            'faviconUrl' => '/favicon.png',
            'allowedCommunitySurnames' => ['Chandrakar', 'Verma', 'Bais'],
            'maintenanceMode' => false,
            'requireProfileApproval' => true,
        ];

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/site', $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siteName', 'Kurmi Samaj')
            ->assertJsonPath('data.allowedCommunitySurnames.0', 'Chandrakar')
            ->assertJsonPath('data.requireProfileApproval', true);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/site')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siteName', 'Kurmi Samaj');
    }

    public function test_candidate_cannot_update_site_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-site@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/admin/settings/site', ['siteName' => 'Nope'])
            ->assertStatus(403);
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
