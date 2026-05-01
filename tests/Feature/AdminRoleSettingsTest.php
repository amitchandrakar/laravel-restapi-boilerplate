<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRoleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_roles_with_permission_counts(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-list@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/roles')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['uuid', 'name', 'permissionCount', 'isSystem']]]);
    }

    public function test_admin_can_fetch_permissions_by_role_uuid(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-perms@example.com');
        $adminRole = Role::query()->where('name', 'admin')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/roles/' . $adminRole->uuid . '/permissions')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'role' => ['uuid', 'name', 'permissionCount'],
                    'permissions' => [['id', 'name', 'title']],
                ],
            ]);
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-create@example.com');
        $permId = (int) Permission::query()->where('name', 'admin.dashboard.view')->value('id');
        $name = 'new_custom_role_' . Str::random(6);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/roles', [
                'name' => $name,
                'title' => 'New role',
                'description' => 'Created via API',
                'permission_ids' => [$permId],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role.name', $name)
            ->assertJsonPath('data.role.isSystem', false);

        $this->assertDatabaseHas('roles', ['name' => $name, 'guard_name' => 'web', 'is_system' => false]);
        $created = Role::query()->where('name', $name)->firstOrFail();
        $this->assertTrue($created->hasPermissionTo('admin.dashboard.view'));
    }

    public function test_create_role_rejects_duplicate_name(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-dup@example.com');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/roles', [
                'name' => 'admin',
                'title' => 'Duplicate',
            ])
            ->assertStatus(422);
    }

    public function test_candidate_cannot_create_role(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'roles-candidate-create@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->postJson('/api/v1/admin/settings/roles', [
                'name' => 'illegal_role',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_update_custom_role_and_sync_permissions(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-custom@example.com');
        $permId = (int) Permission::query()->where('name', 'admin.dashboard.view')->value('id');

        $role = Role::query()->create([
            'name' => 'custom_staff_' . Str::random(6),
            'guard_name' => 'web',
            'uuid' => (string) Str::uuid(),
            'title' => 'Custom Staff',
            'description' => 'Test role',
            'is_system' => false,
            'is_default_registration' => false,
        ]);
        $role->givePermissionTo('admin.dashboard.view');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/roles/' . $role->uuid, [
                'name' => 'custom_staff_renamed',
                'title' => 'Renamed Staff',
                'permission_ids' => [$permId],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role.name', 'custom_staff_renamed')
            ->assertJsonPath('data.role.title', 'Renamed Staff');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'custom_staff_renamed']);
    }

    public function test_system_role_rejects_name_change(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-system-name@example.com');
        $candidateRole = Role::query()->where('name', 'candidate')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/roles/' . $candidateRole->uuid, [
                'name' => 'candidate_renamed',
                'title' => 'Updated title',
            ])
            ->assertStatus(422);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-system-del@example.com');
        $reviewerRole = Role::query()->where('name', 'reviewer')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/settings/roles/' . $reviewerRole->uuid)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_delete_custom_role(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'roles-admin-delete-custom@example.com');

        $role = Role::query()->create([
            'name' => 'deletable_' . Str::random(6),
            'guard_name' => 'web',
            'uuid' => (string) Str::uuid(),
            'title' => 'Deletable',
            'is_system' => false,
            'is_default_registration' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/settings/roles/' . $role->uuid)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_candidate_cannot_list_roles(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'roles-candidate@example.com');

        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/settings/roles')->assertStatus(403);
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
