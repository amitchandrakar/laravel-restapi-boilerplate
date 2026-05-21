<?php

declare(strict_types=1);

use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('lets admins impersonate a candidate and receive an app auth token', function (): void {
    Queue::fake();

    $admin = $this->createUserWithRole('admin', 'admin-imp-' . uniqid('', true) . '@example.com');
    $candidate = $this->createUserWithRole('candidate', 'imp-cand-' . uniqid('', true) . '@example.com');

    $response = $this->actingAs($admin, 'sanctum')->postJson(
        '/api/v1/admin/candidates/' . $candidate->uuid . '/impersonate'
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', $candidate->email)
        ->assertJsonStructure(['data' => ['token', 'session_token_hash', 'expires_at']]);

    Queue::assertPushed(LogAuditJob::class);
    Queue::assertPushed(LogUserActivityJob::class);
});

it('forbids candidates from impersonating other users', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'imp-forbidden-' . uniqid('', true) . '@example.com');
    $other = $this->createUserWithRole('candidate', 'imp-other-' . uniqid('', true) . '@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/admin/candidates/' . $other->uuid . '/impersonate')
        ->assertStatus(403);
});

it('rejects impersonation for non-candidate users', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-imp-team-' . uniqid('', true) . '@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'imp-reviewer-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/' . $reviewer->uuid . '/impersonate')
        ->assertStatus(404);
});
