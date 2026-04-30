<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\UserLifecycleEvent;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminTeamCandidateUserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_team_user_and_dispatch_logs(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-team@example.com');
        $reviewerRoleId = $this->roleIdByName('reviewer');

        Queue::fake();
        Event::fake([UserLifecycleEvent::class]);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/team-users', [
            'name' => 'Team Member',
            'email' => 'team.member@example.com',
            'phone' => '9999999999',
            'gender' => 'male',
            'role_id' => $reviewerRoleId,
            'department' => 'Operations',
            'job_title' => 'Lead',
            'city' => 'Raipur',
            'status' => 'active',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);
        $create->assertStatus(201)->assertJsonPath('data.userType', 'team');
        $teamUserId = (int) $create->json('data.id');
        $teamUserUuid = (string) User::query()->findOrFail($teamUserId)->uuid;
        $this->assertDatabaseHas('users', [
            'id' => $teamUserId,
            'role_id' => $reviewerRoleId,
        ]);
        $this->assertTrue(User::query()->findOrFail($teamUserId)->hasRole('reviewer'));
        $this->assertTrue(User::query()->findOrFail($teamUserId)->can('admin.teams.view'));
        $this->assertFalse(User::query()->findOrFail($teamUserId)->can('admin.settings.roles.edit'));

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/team-users/' . $teamUserUuid)
            ->assertStatus(200)
            ->assertJsonPath('data.department', 'Operations');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/team-users/' . $teamUserUuid, [
                'job_title' => 'Senior Lead',
                'department' => 'QA',
                'role_id' => $this->roleIdByName('admin'),
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.jobTitle', 'Senior Lead');
        $this->assertTrue(User::query()->findOrFail($teamUserId)->hasRole('admin'));
        $this->assertTrue(User::query()->findOrFail($teamUserId)->can('admin.settings.roles.edit'));

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/team-users/' . $teamUserUuid)
            ->assertStatus(200);

        Event::assertDispatched(UserLifecycleEvent::class);
        Queue::assertPushed(LogAuditJob::class);
        Queue::assertPushed(LogUserActivityJob::class);
    }

    public function test_team_user_create_rejects_non_team_role_id(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-team-invalid-role@example.com');
        $candidateRoleId = $this->roleIdByName('candidate');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/team-users', [
                'name' => 'Team Invalid Role',
                'email' => 'team.invalidrole@example.com',
                'phone' => '9999991111',
                'gender' => 'male',
                'role_id' => $candidateRoleId,
                'department' => 'Operations',
                'job_title' => 'Lead',
                'city' => 'Raipur',
                'status' => 'active',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_crud_candidate_user_and_permission_denial_works(): void
    {
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
            'role_id' => $this->roleIdByName($role),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function roleIdByName(string $roleName): int
    {
        return (int) \App\Models\Role::query()->where('name', $roleName)->value('id');
    }
}
