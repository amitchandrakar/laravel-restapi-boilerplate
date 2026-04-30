<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        unset($data['password_confirmation']);
        $this->mapLegacyNameField($data);

        return DB::transaction(function () use ($data) {
            return User::create($data);
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        unset($data['password_confirmation']);
        $this->mapLegacyNameField($data);

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapLegacyNameField(array &$data): void
    {
        if (!isset($data['name'])) {
            return;
        }

        $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
        $data['first_name'] = $parts[0] ?? '';
        $data['last_name'] = $parts[1] ?? '';
        unset($data['name']);
    }
}
