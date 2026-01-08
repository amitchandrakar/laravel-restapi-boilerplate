<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Post permissions (example)
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'publish posts',

            // Comment permissions (example)
            'view comments',
            'create comments',
            'edit comments',
            'delete comments',

            // Admin permissions
            'access admin panel',
            'manage roles',
            'manage permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'publish posts',
            'view comments',
            'delete comments',
            'access admin panel',
        ]);

        // Moderator - can manage content
        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'view users',
            'view posts',
            'edit posts',
            'view comments',
            'edit comments',
            'delete comments',
        ]);

        // User - basic permissions
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo(['view posts', 'create posts', 'view comments', 'create comments']);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
