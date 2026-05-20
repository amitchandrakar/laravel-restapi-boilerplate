<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAuthUsersSeeder extends Seeder
{
    private const PASSWORD = '1234567890';

    public function run(): void
    {
        $guard = (string) config('auth.defaults.guard', RbacSeeder::GUARD);

        $admin = $this->upsertUser(
            email: 'admin@example.com',
            firstName: 'System',
            lastName: 'Admin',
            status: 'active'
        );
        $this->assignRoleIfExists($admin, 'admin', $guard);

        $reviewer = $this->upsertUser(
            email: 'reviewer@example.com',
            firstName: 'Staff',
            lastName: 'Reviewer',
            status: 'active'
        );
        $this->assignRoleIfExists($reviewer, 'reviewer', $guard);
    }

    private function upsertUser(string $email, string $firstName, string $lastName, string $status): User
    {
        $user = User::withTrashed()->where('email', $email)->first();

        if ($user === null) {
            /** @var User $user */
            $user = User::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => self::PASSWORD,
                'status' => $status,
            ]);

            return $user;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => self::PASSWORD,
            'status' => $status,
        ]);
        $user->save();

        return $user;
    }

    private function assignRoleIfExists(User $user, string $roleName, string $guard): void
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();

        if (!($role instanceof Role)) {
            return;
        }

        $user->syncRoles([$roleName]);
        $user->forceFill(['role_id' => $role->id])->save();
    }
}
