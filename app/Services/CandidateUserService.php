<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CandidateUserService
{
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->candidates()->latest()->paginate($perPage);
    }

    public function create(array $data): User
    {
        unset($data['password_confirmation']);

        if (isset($data['name'])) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        $password = $data['password'] ?? null;

        if ($password === null || $password === '') {
            $data['password'] = Str::password(24);
        }

        $data['role_id'] = $this->candidateRoleId();

        return DB::transaction(fn(): User => User::query()->create($data));
    }

    public function update(User $user, array $data): User
    {
        unset($data['password_confirmation']);

        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }

        if (isset($data['name'])) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }
        $data['role_id'] = $this->candidateRoleId();

        return DB::transaction(function () use ($user, $data): User {
            $user->update($data);

            return $user->refresh();
        });
    }

    public function delete(User $user): ?bool
    {
        return DB::transaction(fn() => $user->delete());
    }

    private function candidateRoleId(): ?int
    {
        $roleId = Role::query()->where('name', 'candidate')->value('id');

        return $roleId !== null ? (int) $roleId : null;
    }
}
