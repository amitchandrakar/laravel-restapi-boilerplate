<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedCandidatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lists_featured_published_candidates_only(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'featured-admin@example.com');
        $candidate = $this->createPublishedCandidate('featured-cand@example.com');

        $this->getJson('/api/v1/public/featured-candidates')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
                'isFeatured' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.isFeatured', true);

        $this->getJson('/api/v1/public/featured-candidates')
            ->assertStatus(200)
            ->assertJsonPath('data.0.uuid', $candidate->uuid);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
                'isFeatured' => false,
            ])
            ->assertStatus(200);

        $this->getJson('/api/v1/public/featured-candidates')->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_unpublished_candidate_cannot_be_featured_and_reviewer_forbidden(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'featured-admin2@example.com');
        $reviewer = $this->createUserWithRole('reviewer', 'featured-reviewer@example.com');
        $draft = $this->createUserWithRole('candidate', 'featured-draft@example.com');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $draft->uuid . '/featured', [
                'isFeatured' => true,
            ])
            ->assertStatus(422);

        $published = $this->createPublishedCandidate('featured-pub@example.com');

        $this->actingAs($reviewer, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $published->uuid . '/featured', [
                'isFeatured' => true,
            ])
            ->assertStatus(403);
    }

    public function test_public_featured_uses_default_image_when_user_has_no_photos(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'featured-admin-image@example.com');
        $candidate = $this->createPublishedCandidate('featured-no-photo@example.com');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
                'isFeatured' => true,
            ])
            ->assertStatus(200);

        $this->getJson('/api/v1/public/featured-candidates')
            ->assertStatus(200)
            ->assertJsonPath('data.0.photoUrl', '/images/Coming-Soon.png');
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createPublishedCandidate(string $email): User
    {
        $user = $this->createUserWithRole('candidate', $email);
        $user
            ->forceFill([
                'profile_status' => 'published',
                'published_at' => now(),
            ])
            ->save();

        return $user->fresh();
    }
}
