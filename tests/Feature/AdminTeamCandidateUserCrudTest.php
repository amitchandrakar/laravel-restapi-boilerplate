<?php

declare(strict_types=1);

use App\Events\TeamMemberLifecycleEvent;
use App\Events\UserLifecycleEvent;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('supports full team-user CRUD while dispatching supporting log jobs', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-team@example.com');
    $reviewerRoleId = roleIdByName('reviewer');
    $teamsViewPermissionId = permissionIdByName('admin.teams.view');

    Queue::fake();
    Event::fake([UserLifecycleEvent::class, TeamMemberLifecycleEvent::class]);

    $create = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/team-users', [
        'first_name' => 'Team',
        'last_name' => 'Member',
        'email' => 'team.member@example.com',
        'phone' => '9999999999',
        'gender' => 'male',
        'role_id' => $reviewerRoleId,
        'permission_ids' => [$teamsViewPermissionId],
        'city' => 'Raipur',
        'state' => 'Chhattisgarh',
        'country' => 'India',
        'about' => 'Operations lead for the platform.',
        'department' => 'Operations',
        'job_title' => 'Lead',
        'status' => 'active',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);
    $create
        ->assertStatus(201)
        ->assertJsonPath('data.userType', 'team')
        ->assertJsonPath('data.firstName', 'Team')
        ->assertJsonPath('data.location.city', 'Raipur')
        ->assertJsonPath('data.about', 'Operations lead for the platform.');
    $teamUserId = (int) $create->json('data.id');
    $teamUserUuid = (string) User::query()->findOrFail($teamUserId)->uuid;
    $this->assertDatabaseHas('users', [
        'id' => $teamUserId,
        'role_id' => $reviewerRoleId,
        'current_state' => 'Chhattisgarh',
        'current_country' => 'India',
    ]);
    expect(User::query()->findOrFail($teamUserId)->hasRole('reviewer'))->toBeTrue();
    expect(User::query()->findOrFail($teamUserId)->can('admin.teams.view'))->toBeTrue();
    expect(User::query()->findOrFail($teamUserId)->can('admin.settings.roles.edit'))->toBeFalse();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/team-users/' . $teamUserUuid)
        ->assertStatus(200)
        ->assertJsonPath('data.department', 'Operations');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/team-users?search=team.member&role_id=' . $reviewerRoleId . '&city=Raipur')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/team-users/' . $teamUserUuid, [
            'job_title' => 'Senior Lead',
            'department' => 'QA',
            'role_id' => roleIdByName('admin'),
            'permission_ids' => [],
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.jobTitle', 'Senior Lead');
    expect(User::query()->findOrFail($teamUserId)->hasRole('admin'))->toBeTrue();
    expect(User::query()->findOrFail($teamUserId)->can('admin.settings.roles.edit'))->toBeTrue();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/admin/team-users/' . $teamUserUuid)
        ->assertStatus(200);

    Event::assertDispatched(UserLifecycleEvent::class);
    Event::assertDispatched(TeamMemberLifecycleEvent::class);
    Queue::assertPushed(LogAuditJob::class);
    Queue::assertPushed(LogUserActivityJob::class);
});

it('exposes assignable permission options for team forms', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-team-perms@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/team-users/permission-options')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'module' => ['id', 'code', 'name'],
                    'permissions' => [
                        '*' => ['id', 'name', 'title'],
                    ],
                ],
            ],
        ]);
});

it('rejects team onboarding when the referenced role identifier is not a team role', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-team-invalid-role@example.com');
    $candidateRoleId = roleIdByName('candidate');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/team-users', [
            'first_name' => 'Team',
            'last_name' => 'Invalid',
            'email' => 'team.invalidrole@example.com',
            'phone' => '9999991111',
            'gender' => 'male',
            'role_id' => $candidateRoleId,
            'city' => 'Raipur',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])
        ->assertStatus(422);
});

it('handles candidate-facing admin CRUD and surfaces permission denial correctly', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-candidate@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-no-access@example.com');

    Queue::fake();
    Event::fake([UserLifecycleEvent::class]);

    $create = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/candidates', [
        'name' => 'Candidate Member',
        'email' => 'candidate.member@example.com',
        'phone' => '9888888888',
        'gender' => 'female',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);
    $create->assertStatus(201)->assertJsonPath('data.userType', 'candidate');
    $candidateId = (int) $create->json('data.id');
    $candidateUuid = (string) User::query()->findOrFail($candidateId)->uuid;

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidateUuid, [
            'phone' => '9777777777',
            'current_city' => 'Bilaspur',
        ])
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/candidates')->assertStatus(403);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/admin/candidates/' . $candidateUuid)
        ->assertStatus(200);

    Event::assertDispatched(UserLifecycleEvent::class);
    Queue::assertPushed(LogAuditJob::class);
    Queue::assertPushed(LogUserActivityJob::class);
});

function roleIdByName(string $roleName): int
{
    return (int) Role::query()->where('name', $roleName)->value('id');
}

function permissionIdByName(string $permissionName): int
{
    return (int) Permission::query()->where('name', $permissionName)->value('id');
}
