<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PackagePermissionService
{
    public function syncCandidatePermissions(User $user): void
    {
        if (!$user->hasRole('candidate')) {
            return;
        }

        $activePackageId = (int) DB::table('subscriptions')
            ->where('subscriptions.user_id', $user->id)
            ->where('subscriptions.subscription_status', 'active')
            ->whereExists(function (Builder $query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('packages')
                    ->whereColumn('packages.id', 'subscriptions.package_id')
                    ->whereNull('packages.deleted_at')
                    ->where('packages.is_active', true);
            })
            ->orderByDesc('subscriptions.id')
            ->value('subscriptions.package_id');

        $targetPermissions = $this->permissionNamesForPackageId($activePackageId);
        $packagePermissionUniverse = $this->allCandidatePackagePermissionNames();

        $existingPackageDirectPermissions = $user
            ->permissions()
            ->whereIn('name', $packagePermissionUniverse)
            ->pluck('name')
            ->all();

        foreach ($existingPackageDirectPermissions as $permissionName) {
            $user->revokePermissionTo($permissionName);
        }

        if ($targetPermissions === []) {
            return;
        }

        $validPermissionNames = Permission::query()->whereIn('name', $targetPermissions)->pluck('name')->all();

        if ($validPermissionNames !== []) {
            $user->givePermissionTo($validPermissionNames);
        }
    }

    /**
     * @return array<int, string>
     */
    public function permissionNamesForPackageId(int $packageId): array
    {
        if ($packageId <= 0) {
            return [];
        }

        return DB::table('package_permissions')
            ->join('permissions', 'permissions.id', '=', 'package_permissions.permission_id')
            ->where('package_permissions.package_id', $packageId)
            ->where('permissions.name', 'like', 'candidate.%')
            ->pluck('permissions.name')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function permissionNamesForPackageCode(string $code): array
    {
        $packageId = (int) DB::table('packages')->where('code', strtoupper($code))->value('id');

        return $this->permissionNamesForPackageId($packageId);
    }

    /**
     * @return array<int, string>
     */
    public function allCandidatePackagePermissionNames(): array
    {
        return Permission::query()->where('name', 'like', 'candidate.%')->pluck('name')->all();
    }

    public function syncCandidatesForPackage(Package $package): void
    {
        $candidateUserIds = DB::table('subscriptions')
            ->join('model_has_roles', function ($join): void {
                $join
                    ->on('model_has_roles.model_id', '=', 'subscriptions.user_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('subscriptions.package_id', $package->id)
            ->where('subscriptions.subscription_status', 'active')
            ->where('roles.name', 'candidate')
            ->pluck('subscriptions.user_id')
            ->unique()
            ->values();

        foreach ($candidateUserIds as $userId) {
            /** @var User|null $user */
            $user = User::query()->find((int) $userId);
            if ($user instanceof User) {
                $this->syncCandidatePermissions($user);
            }
        }
    }

    /**
     * @return array<int, array{id:int,name:string,title:string}>
     */
    public function candidatePermissionOptions(): array
    {
        return Permission::query()
            ->where('name', 'like', 'candidate.%')
            ->orderBy('name')
            ->get(['id', 'name', 'title'])
            ->map(
                static fn(Permission $permission): array => [
                    'id' => (int) $permission->id,
                    'name' => (string) $permission->name,
                    'title' => (string) ($permission->title ?? $permission->name),
                ]
            )
            ->values()
            ->all();
    }
}
