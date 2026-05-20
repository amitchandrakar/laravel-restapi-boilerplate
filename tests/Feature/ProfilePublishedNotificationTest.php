<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\CandidateProfileSectionService;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

it('notifies candidates when admins publish their profile', function () {
    /** @var User $admin */
    $admin = User::query()->create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin-pub-' . uniqid('', true) . '@example.com',
        'password' => 'Password@123',
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $candidateRoleId = (int) DB::table('roles')
        ->where('name', 'candidate')
        ->where('guard_name', 'web')
        ->value('id');

    /** @var User $candidate */
    $candidate = User::query()->create([
        'first_name' => 'Cand',
        'last_name' => 'Pub',
        'email' => 'cand-pub-' . uniqid('', true) . '@example.com',
        'password' => 'Password@123',
        'status' => 'active',
        'role_id' => $candidateRoleId > 0 ? $candidateRoleId : null,
    ]);
    $candidate->assignRole('candidate');

    $candidate
        ->forceFill([
            'completed_sections_json' => CandidateProfileSectionService::sections(),
            'profile_status' => 'draft',
        ])
        ->save();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/candidates/' . $candidate->uuid . '/publish')
        ->assertStatus(200);

    expect($candidate->fresh()->notifications()->where('data->kind', 'profile_published')->count())->toBe(1);

    $res = $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/auth/notifications')->assertStatus(200);
    $items = (array) $res->json('data');
    $kinds = collect($items)->pluck('kind')->all();
    expect($kinds)->toContain('profile_published');
});
