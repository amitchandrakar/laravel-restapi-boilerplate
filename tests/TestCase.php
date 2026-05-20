<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seeded RBAC helper: creates a {@see User}, assigns {@see Role} name, logs in-ready.
     */
    protected function createUserWithRole(string $role, string $email, string $lastName = 'User'): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => $lastName,
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->where('guard_name', 'web')->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
