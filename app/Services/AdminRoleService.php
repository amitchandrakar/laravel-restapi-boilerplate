<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class AdminRoleService
{
    private const GUARD = 'web';

    /**
     * @return list<array<string, mixed>>
     */
    public function index(): array
    {
        return Role::query()
            ->where('guard_name', self::GUARD)
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->map(static fn(Role $role): array => self::roleSummary($role))
            ->values()
            ->all();
    }

    /**
     * @return array{role: array<string, mixed>, permissions: list<array<string, mixed>>}
     */
    public function permissionsForRole(Role $role): array
    {
        $this->assertGuard($role);

        $permissions = $role
            ->permissions()
            ->with('module')
            ->orderBy('permissions.name')
            ->get()
            ->map(static function (Model $model): array {
                if (!$model instanceof Permission) {
                    throw new \LogicException('Expected Permission model from role permissions relation.');
                }

                $module = $model->relationLoaded('module') ? $model->module : null;

                return [
                    'id' => (int) $model->id,
                    'uuid' => $model->uuid,
                    'name' => $model->name,
                    'title' => $model->title,
                    'guardName' => $model->guard_name,
                    'module' => $module instanceof Module
                            ? [
                                'id' => (int) $module->id,
                                'code' => $module->code,
                                'name' => $module->name,
                            ]
                            : null,
                ];
            })
            ->values()
            ->all();

        return [
            'role' => self::roleSummary($role),
            'permissions' => $permissions,
        ];
    }

    /**
     * Create a custom (non-system) role for the web guard and optionally sync permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            if (array_key_exists('is_default_registration', $data) && (bool) $data['is_default_registration']) {
                Role::query()
                    ->where('guard_name', self::GUARD)
                    ->update(['is_default_registration' => false]);
            }

            $role = Role::query()->create([
                'name' => (string) $data['name'],
                'guard_name' => self::GUARD,
                'uuid' => (string) Str::uuid(),
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'is_system' => false,
                'is_default_registration' => (bool) ($data['is_default_registration'] ?? false),
            ]);

            if (array_key_exists('permission_ids', $data) && is_array($data['permission_ids'])) {
                $ids = array_values(array_unique(array_map('intval', $data['permission_ids'])));
                $role->syncPermissions($ids);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $this->assertGuard($role);

        return DB::transaction(function () use ($role, $data): Role {
            if (array_key_exists('is_default_registration', $data) && (bool) $data['is_default_registration']) {
                Role::query()
                    ->where('guard_name', self::GUARD)
                    ->where('id', '!=', $role->id)
                    ->update(['is_default_registration' => false]);
            }

            $payload = [];

            if (!$role->is_system && array_key_exists('name', $data)) {
                $payload['name'] = (string) $data['name'];
            }

            if (array_key_exists('title', $data)) {
                $payload['title'] = $data['title'];
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }

            if (array_key_exists('is_default_registration', $data)) {
                $payload['is_default_registration'] = (bool) $data['is_default_registration'];
            }

            if ($payload !== []) {
                $role->update($payload);
            }

            if (array_key_exists('permission_ids', $data) && is_array($data['permission_ids'])) {
                $ids = array_values(array_unique(array_map('intval', $data['permission_ids'])));
                $role->syncPermissions($ids);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->refresh();
        });
    }

    public function delete(Role $role): void
    {
        $this->assertGuard($role);

        if ($role->is_system) {
            throw new \InvalidArgumentException('System roles cannot be deleted.');
        }

        DB::transaction(function () use ($role): void {
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    private function assertGuard(Role $role): void
    {
        if ($role->guard_name !== self::GUARD) {
            throw new \InvalidArgumentException('Role guard is not supported.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function roleSummary(Role $role): array
    {
        return [
            'id' => $role->id,
            'uuid' => $role->uuid,
            'name' => $role->name,
            'title' => $role->title,
            'description' => $role->description,
            'guardName' => $role->guard_name,
            'isSystem' => (bool) $role->is_system,
            'isDefaultRegistration' => (bool) ($role->is_default_registration ?? false),
            'permissionCount' => isset($role->permissions_count)
                ? (int) $role->permissions_count
                : (int) $role->permissions()->count(),
            'createdAt' => $role->created_at,
            'updatedAt' => $role->updated_at,
        ];
    }
}
