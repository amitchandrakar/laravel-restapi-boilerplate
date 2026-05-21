<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TeamUserService
{
    public const SORT_OPTIONS = ['latest', 'oldest', 'name'];

    private const ALLOWED_TEAM_ROLE_NAMES = ['admin', 'reviewer'];

    public function __construct(
        private readonly TeamUserPermissionService $permissionService,
        private readonly TeamUserProfilePhotoService $profilePhotoService
    ) {}

    /**
     * Paginated team user list with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));

        return $this->buildListQuery($filters)
            ->with(['primaryRole', 'permissions.module'])
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $profilePhoto = null): User
    {
        return DB::transaction(function () use ($data, $profilePhoto): User {
            $role = $this->resolveAssignableRole($data);
            $payload = $this->mapPayload($data);
            $payload['role_id'] = $role?->id;
            $payload['status'] = $payload['status'] ?? 'active';

            /** @var User $user */
            $user = User::query()->create($payload);

            if ($role instanceof Role) {
                $this->applyTeamRole($user, $role);
            }

            $this->syncPermissionsFromPayload($user, $data);

            if ($profilePhoto instanceof UploadedFile) {
                $this->profilePhotoService->store($user, $profilePhoto);
            }

            Log::info('TeamUserService: team user created', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
            ]);

            return $user->refresh()->load(['primaryRole', 'permissions.module']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?UploadedFile $profilePhoto = null): User
    {
        return DB::transaction(function () use ($user, $data, $profilePhoto): User {
            $role = $this->resolveAssignableRole($data);
            $payload = $this->mapPayload($data);

            if ($role instanceof Role) {
                $payload['role_id'] = $role->id;
            }

            $user->update($payload);

            if ($role instanceof Role) {
                $this->applyTeamRole($user, $role);
            }

            if (array_key_exists('permission_ids', $data)) {
                $this->syncPermissionsFromPayload($user, $data);
            }

            if ($profilePhoto instanceof UploadedFile) {
                $this->profilePhotoService->store($user, $profilePhoto);
            }

            Log::info('TeamUserService: team user updated', ['user_id' => $user->id]);

            return $user->refresh()->load(['primaryRole', 'permissions.module']);
        });
    }

    public function delete(User $user): ?bool
    {
        return DB::transaction(function () use ($user): ?bool {
            Log::info('TeamUserService: team user deleted', ['user_id' => $user->id]);

            return $user->delete();
        });
    }

    /**
     * Snapshot of auditable attributes before update/delete.
     *
     * @return array<string, mixed>
     */
    public function auditSnapshot(User $user): array
    {
        return $user->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'role_id',
            'department',
            'job_title',
            'current_city',
            'current_state',
            'current_country',
            'about_me',
            'profile_photo_url',
            'status',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Builder<User>
     */
    public function buildListQuery(array $filters = []): Builder
    {
        $query = User::query()->teamUsers();

        if (!empty($filters['search'])) {
            $term = '%' . addcslashes((string) $filters['search'], '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($term): void {
                $builder
                    ->where('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        if (!empty($filters['role_id'])) {
            $query->where('role_id', (int) $filters['role_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', (string) $filters['gender']);
        }

        if (!empty($filters['city'])) {
            $query->where('current_city', 'like', '%' . addcslashes((string) $filters['city'], '%_\\') . '%');
        }

        if (!empty($filters['state'])) {
            $query->where('current_state', 'like', '%' . addcslashes((string) $filters['state'], '%_\\') . '%');
        }

        if (!empty($filters['country'])) {
            $query->where('current_country', 'like', '%' . addcslashes((string) $filters['country'], '%_\\') . '%');
        }

        if (!empty($filters['department'])) {
            $query->where('department', 'like', '%' . addcslashes((string) $filters['department'], '%_\\') . '%');
        }

        $sort = (string) ($filters['sort'] ?? 'latest');

        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            default => $query->latest(),
        };

        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapPayload(array $data): array
    {
        unset($data['password_confirmation'], $data['permission_ids'], $data['profile_photo']);

        if (isset($data['city'])) {
            $data['current_city'] = $data['city'];
            unset($data['city']);
        }

        if (isset($data['state'])) {
            $data['current_state'] = $data['state'];
            unset($data['state']);
        }

        if (isset($data['country'])) {
            $data['current_country'] = $data['country'];
            unset($data['country']);
        }

        if (isset($data['about'])) {
            $data['about_me'] = $data['about'];
            unset($data['about']);
        }

        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            $data['password'] = Hash::make((string) $data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    private function applyTeamRole(User $user, Role $role): void
    {
        $user->forceFill(['role_id' => $role->id])->save();
        $user->syncRoles([$role->name]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPermissionsFromPayload(User $user, array $data): void
    {
        if (!array_key_exists('permission_ids', $data)) {
            return;
        }

        $ids = array_map(static fn(mixed $id): int => (int) $id, (array) ($data['permission_ids'] ?? []));
        $this->permissionService->syncDirectPermissions($user, $ids);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAssignableRole(array $data): ?Role
    {
        if (!isset($data['role_id'])) {
            return null;
        }

        $role = Role::query()->find((int) $data['role_id']);

        if (!($role instanceof Role) || !in_array($role->name, self::ALLOWED_TEAM_ROLE_NAMES, true)) {
            throw new ModelNotFoundException('Invalid team role selected.');
        }

        return $role;
    }
}
