<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $permissions = $user->getAllPermissions();
        $permissions->each(static function (Permission $permission): void {
            $permission->loadMissing('module');
        });
        $modules = $permissions
            ->filter(static fn(Permission $permission) => data_get($permission, 'module.id') !== null)
            ->map(static function ($permission): array {
                return [
                    'id' => (int) data_get($permission, 'module.id'),
                    'code' => (string) data_get($permission, 'module.code', ''),
                    'name' => (string) data_get($permission, 'module.name', ''),
                ];
            })
            ->unique('id')
            ->values();

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'userType' => 'team',
            'roleId' => $user->role_id,
            'role' => data_get($user, 'primaryRole.name'),
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'profilePhoto' => $user->profile_photo_url,
            'department' => $user->department,
            'jobTitle' => $user->job_title,
            'city' => $user->current_city,
            'status' => $user->status,
            'permissions' => $permissions->pluck('name')->values()->all(),
            'modules' => $modules->all(),
        ];
    }
}
