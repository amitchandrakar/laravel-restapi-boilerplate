<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Retrieve all users with pagination.
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return DB::transaction(function () use ($data) {
            return User::create($data);
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return DB::transaction(function () use ($user, $data) {
            $user->update($data);

            return $user->refresh();
        });
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): ?bool
    {
        return DB::transaction(function () use ($user) {
            return $user->delete();
        });
    }
}
