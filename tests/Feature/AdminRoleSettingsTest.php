<?php

declare(strict_types=1);
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

describe('admin role settings', function (): void {
    it('allows admins to list roles with permission counts', function (): void {
        $admin = $this->createUserWithRole('admin', 'roles-admin-list@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/roles')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['uuid', 'name', 'permissionCount', 'isSystem']]]);
    });

    it('allows admins to load permissions for a role by UUID', function () {
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
    });

    it('allows admins to create custom roles with permission grants', function () {
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
        expect($created->hasPermissionTo('admin.dashboard.view'))->toBeTrue();
    });

    it('rejects creating a role whose name already exists', function () {
        $admin = $this->createUserWithRole('admin', 'roles-admin-dup@example.com');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/roles', [
                'name' => 'admin',
                'title' => 'Duplicate',
            ])
            ->assertStatus(422);
    });

    it('returns forbidden when a candidate attempts to create a role', function () {
        $candidate = $this->createUserWithRole('candidate', 'roles-candidate-create@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->postJson('/api/v1/admin/settings/roles', [
                'name' => 'illegal_role',
            ])
            ->assertStatus(403);
    });

    it('allows admins to rename non-system roles and sync their permissions', function () {
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
    });

    it('rejects renaming built-in system roles', function () {
        $admin = $this->createUserWithRole('admin', 'roles-admin-system-name@example.com');
        $candidateRole = Role::query()->where('name', 'candidate')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/roles/' . $candidateRole->uuid, [
                'name' => 'candidate_renamed',
                'title' => 'Updated title',
            ])
            ->assertStatus(422);
    });

    it('rejects deleting built-in system roles', function () {
        $admin = $this->createUserWithRole('admin', 'roles-admin-system-del@example.com');
        $reviewerRole = Role::query()->where('name', 'reviewer')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/settings/roles/' . $reviewerRole->uuid)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('allows admins to delete custom roles', function () {
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
    });

    it('returns forbidden when a candidate lists admin roles', function () {
        $candidate = $this->createUserWithRole('candidate', 'roles-candidate@example.com');

        $this->actingAs($candidate, 'sanctum')->getJson('/api/v1/admin/settings/roles')->assertStatus(403);
    });
});
