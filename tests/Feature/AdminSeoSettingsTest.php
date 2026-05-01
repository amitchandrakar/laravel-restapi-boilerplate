<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_fetch_seo_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-seo@example.com');

        $payload = [
            'siteTitle' => 'Kurmi Samaj Matrimonial',
            'defaultDescription' => 'Find your perfect life partner.',
            'defaultKeywords' => 'Kurmi matrimony, Kurmi shaadi',
            'canonicalBaseUrl' => 'https://example.com',
            'gaEnabled' => true,
            'gaTrackingCode' => '<script>window.test=true;</script>',
            'robotsEnabled' => true,
            'robotsTxtContent' => "User-agent: *\nDisallow:",
            'sitemapEnabled' => true,
            'sitemapUrls' => ['/', '/browse'],
            'ogImage' => '/og-image.jpg',
            'ogType' => 'website',
            'twitterCard' => 'summary_large_image',
            'twitterTitle' => 'Twitter SEO title',
            'twitterDescription' => 'Twitter SEO description',
            'twitterImage' => '/twitter-image.jpg',
        ];

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/seo', $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siteTitle', 'Kurmi Samaj Matrimonial')
            ->assertJsonPath('data.gaEnabled', true)
            ->assertJsonPath('data.sitemapUrls.0', '/');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/seo')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siteTitle', 'Kurmi Samaj Matrimonial')
            ->assertJsonPath('data.twitterCard', 'summary_large_image');
    }

    public function test_candidate_cannot_update_seo_settings(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-seo@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/admin/settings/seo', ['siteTitle' => 'Nope'])
            ->assertStatus(403);
    }

    public function test_validation_for_invalid_seo_payload(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-seo-validation@example.com');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/seo', [
                'canonicalBaseUrl' => 'invalid-url',
                'twitterCard' => 'invalid-card',
            ])
            ->assertStatus(422);
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
