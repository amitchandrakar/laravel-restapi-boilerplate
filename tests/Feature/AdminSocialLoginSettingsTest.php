<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSocialLoginSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_fetch_social_login_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-social@example.com');

        $payload = [
            'googleEnabled' => true,
            'googleEnvironment' => 'live',
            'googleLiveClientId' => 'google-client-id',
            'googleLiveClientSecret' => 'google-client-secret',
            'googleLiveRedirectUrl' => 'https://example.com/auth/google/callback',

            'facebookEnabled' => true,
            'facebookEnvironment' => 'live',
            'facebookLiveClientId' => 'facebook-client-id',
            'facebookLiveClientSecret' => 'facebook-client-secret',
            'facebookLiveRedirectUrl' => 'https://example.com/auth/facebook/callback',

            'instagramEnabled' => true,
            'instagramEnvironment' => 'live',
            'instagramLiveClientId' => 'instagram-client-id',
            'instagramLiveClientSecret' => 'instagram-client-secret',
            'instagramLiveRedirectUrl' => 'https://example.com/auth/instagram/callback',
        ];

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/social-login', $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.googleEnabled', true)
            ->assertJsonPath('data.facebookEnvironment', 'live')
            ->assertJsonPath('data.instagramLiveClientId', 'instagram-client-id');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/social-login')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.googleLiveClientId', 'google-client-id');
    }

    public function test_candidate_cannot_update_social_login_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-social@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/admin/settings/social-login', ['googleEnabled' => true])
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
