<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('filters candidates by admin list buckets', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-list-' . uniqid('', true) . '@example.com');

    $published = $this->createUserWithRole('candidate', 'pub-' . uniqid('', true) . '@example.com');
    $published->update([
        'profile_status' => 'published',
        'published_at' => now(),
        'is_featured' => false,
    ]);

    $review = $this->createUserWithRole('candidate', 'rev-' . uniqid('', true) . '@example.com');
    $review->update(['profile_status' => 'under_review']);

    $spam = $this->createUserWithRole('candidate', 'spam-' . uniqid('', true) . '@example.com');
    $spam->update(['profile_status' => 'spam']);

    $featured = $this->createUserWithRole('candidate', 'feat-' . uniqid('', true) . '@example.com');
    $featured->update([
        'profile_status' => 'published',
        'published_at' => now(),
        'is_featured' => true,
    ]);

    $deleted = $this->createUserWithRole('candidate', 'del-' . uniqid('', true) . '@example.com');
    $deleted->delete();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates?bucket=published')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $publishedIds = collect(
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/candidates?bucket=published')->json('data')
    )
        ->pluck('id')
        ->map(static fn($id): int => (int) $id);

    expect($publishedIds)->toContain((int) $published->id)->and($publishedIds)->not->toContain((int) $review->id);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates?bucket=under_review')
        ->assertStatus(200)
        ->assertJsonPath('data.0.profileStatus', 'under_review');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates?bucket=spam')
        ->assertStatus(200)
        ->assertJsonPath('data.0.profileStatus', 'spam');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates?bucket=featured')
        ->assertStatus(200)
        ->assertJsonPath('data.0.isFeatured', true);

    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/candidates?bucket=deleted')->assertStatus(200);

    $deletedUuids = collect(
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/candidates?bucket=deleted')->json('data')
    )->pluck('uuid');

    expect($deletedUuids)->toContain($deleted->uuid);
});

it('updates and restores candidate profile status with audit-friendly flow', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-status-' . uniqid('', true) . '@example.com');
    $candidate = $this->createUserWithRole('candidate', 'cand-status-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-status', [
            'profile_status' => 'under_review',
            'reason' => 'Pending staff review',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.profileStatus', 'under_review');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-status', [
            'profile_status' => 'published',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.profileStatus', 'published');

    expect(User::query()->find($candidate->id)?->published_at)->not->toBeNull();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/admin/candidates/' . $candidate->uuid)
        ->assertStatus(200);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/' . $candidate->uuid . '/restore')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(User::query()->find($candidate->id))->not->toBeNull();
});

it('validates store payload for admin candidate create', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-store-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/candidates', [])->assertStatus(422);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates', [
            'first_name' => 'Riya',
            'last_name' => 'Verma',
            'email' => 'riya-' . uniqid('', true) . '@example.com',
            'phone' => '9876501234',
            'gender' => 'female',
            'marital_status' => 'single',
            'height' => '5ft 4in',
            'blood_group' => 'B+',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.userType', 'candidate')
        ->assertJsonPath('data.firstName', 'Riya');
});

it('exports candidates as CSV with expected headers', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-export-' . uniqid('', true) . '@example.com');
    $this->createUserWithRole('candidate', 'export-cand-' . uniqid('', true) . '@example.com');

    $response = $this->actingAs($admin, 'sanctum')->get('/api/v1/admin/candidates/export?bucket=all');

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = method_exists($response, 'streamedContent')
        ? (string) $response->streamedContent()
        : (string) $response->getContent();
    $lines = preg_split("/\r\n|\n|\r/", trim($content)) ?: [];
    expect($lines[0] ?? '')->toBe(
        'uuid,first_name,last_name,email,phone,gender,marital_status,height,weight,blood_group,body_type,profile_status,status,is_featured,published_at,created_at,updated_at'
    );
});
