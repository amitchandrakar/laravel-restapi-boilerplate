<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TeamUserService
{
    private const ALLOWED_TEAM_ROLE_NAMES = ['admin', 'reviewer'];

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->teamUsers()->latest()->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = $this->resolveAssignableRole($data);
            $payload = $this->mapPayload($data);
            $payload['role_id'] = $role?->id;

            /** @var User $user */
            $user = User::query()->create($payload);
            if ($role instanceof Role) {
                $this->applyTeamRole($user, $role);
            }

            return $user->refresh();
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $role = $this->resolveAssignableRole($data);
            $payload = $this->mapPayload($data);
            if ($role instanceof Role) {
                $payload['role_id'] = $role->id;
            }
            $user->update($payload);
            if ($role instanceof Role) {
                $this->applyTeamRole($user, $role);
            }

            return $user->refresh();
        });
    }

    public function delete(User $user): ?bool
    {
        return DB::transaction(fn() => $user->delete());
    }

    private function mapPayload(array $data): array
    {
        unset($data['password_confirmation']);
        if (isset($data['name'])) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }
        if (isset($data['city'])) {
            $data['current_city'] = $data['city'];
            unset($data['city']);
        }

        return $data;
    }

    private function applyTeamRole(User $user, Role $role): void
    {
        $user->forceFill(['role_id' => $role->id])->save();
        $user->syncRoles([$role->name]);
    }

    private function resolveAssignableRole(array $data): ?Role
    {
        if (!isset($data['role_id'])) {
            return null;
        }

        $role = Role::query()->find((int) $data['role_id']);
        if (!$role instanceof Role || !in_array($role->name, self::ALLOWED_TEAM_ROLE_NAMES, true)) {
            throw new ModelNotFoundException('Invalid team role selected.');
        }

        return $role;
    }
}
