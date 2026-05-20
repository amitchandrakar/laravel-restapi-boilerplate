<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('lists only published featured candidates on the public feed', function (): void {
    $admin = $this->createUserWithRole('admin', 'featured-admin@example.com');
    $candidate = createFeaturedPublishedCandidate('featured-cand@example.com');

    $this->getJson('/api/v1/app/public/featured-candidates')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', []);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
            'isFeatured' => true,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.isFeatured', true);

    $this->getJson('/api/v1/app/public/featured-candidates')
        ->assertStatus(200)
        ->assertJsonPath('data.0.uuid', $candidate->uuid);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
            'isFeatured' => false,
        ])
        ->assertStatus(200);

    $this->getJson('/api/v1/app/public/featured-candidates')->assertStatus(200)->assertJsonPath('data', []);
});

it('blocks featuring unpublished profiles and rejects unauthorized reviewers', function (): void {
    $admin = $this->createUserWithRole('admin', 'featured-admin2@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'featured-reviewer@example.com');
    $draft = $this->createUserWithRole('candidate', 'featured-draft@example.com');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $draft->uuid . '/featured', [
            'isFeatured' => true,
        ])
        ->assertStatus(422);

    $published = createFeaturedPublishedCandidate('featured-pub@example.com');

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $published->uuid . '/featured', [
            'isFeatured' => true,
        ])
        ->assertStatus(403);
});

it('uses the default hero image when featured candidates have no gallery photos', function (): void {
    $admin = $this->createUserWithRole('admin', 'featured-admin-image@example.com');
    $candidate = createFeaturedPublishedCandidate('featured-no-photo@example.com');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/featured', [
            'isFeatured' => true,
        ])
        ->assertStatus(200);

    $this->getJson('/api/v1/app/public/featured-candidates')
        ->assertStatus(200)
        ->assertJsonPath('data.0.photoUrl', '/images/Coming-Soon.png');
});
function createFeaturedPublishedCandidate(string $email): User
{
    $user = test()->createUserWithRole('candidate', $email);
    $user
        ->forceFill([
            'profile_status' => 'published',
            'published_at' => now(),
        ])
        ->save();

    return $user->fresh();
}
