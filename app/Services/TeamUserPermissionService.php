<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class TeamUserPermissionService
{
    /**
     * Admin-panel permissions assignable to team members (checkbox list).
     *
     * @return list<array{module: array{id: int, code: string, name: string}, permissions: list<array{id: int, name: string, title: string|null}>}>
     */
    public function assignableOptions(): array
    {
        $permissions = Permission::query()
            ->with('module')
            ->where('guard_name', 'web')
            ->where('name', 'like', 'admin.%')
            ->orderBy('name')
            ->get();

        $grouped = [];

        foreach ($permissions as $permission) {
            $moduleId = (int) ($permission->module_id ?? 0);
            $moduleCode = (string) data_get($permission, 'module.code', 'general');
            $moduleName = (string) data_get($permission, 'module.name', 'General');

            if (!isset($grouped[$moduleId])) {
                $grouped[$moduleId] = [
                    'module' => [
                        'id' => $moduleId,
                        'code' => $moduleCode,
                        'name' => $moduleName,
                    ],
                    'permissions' => [],
                ];
            }

            $grouped[$moduleId]['permissions'][] = [
                'id' => (int) $permission->id,
                'name' => (string) $permission->name,
                'title' => $permission->title,
            ];
        }

        return array_values($grouped);
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function syncDirectPermissions(User $user, array $permissionIds): void
    {
        if ($permissionIds === []) {
            $user->syncPermissions([]);

            return;
        }

        $names = Permission::query()
            ->whereIn('id', $permissionIds)
            ->where('guard_name', 'web')
            ->where('name', 'like', 'admin.%')
            ->pluck('name')
            ->all();

        if (count($names) !== count(array_unique($permissionIds))) {
            Log::warning('TeamUserPermissionService: invalid permission ids submitted', [
                'user_id' => $user->id,
                'requested_ids' => $permissionIds,
            ]);

            throw ValidationException::withMessages([
                'permission_ids' => ['One or more permissions are not assignable to team members.'],
            ]);
        }

        $user->syncPermissions($names);

        Log::info('TeamUserPermissionService: synced direct permissions', [
            'user_id' => $user->id,
            'permission_count' => count($names),
        ]);
    }
}
