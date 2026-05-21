<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CandidateUserService
{
    /** @var list<string> */
    public const PROFILE_STATUSES = ['draft', 'under_review', 'published', 'suspended', 'spam'];

    /** @var list<string> */
    public const LIST_BUCKETS = ['all', 'published', 'under_review', 'suspended', 'featured', 'spam', 'deleted'];

    /**
     * Paginated admin candidate list with bucket and attribute filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));

        return $this->buildListQuery($filters)->paginate($perPage);
    }

    /**
     * Candidates matching list filters (no pagination) for CSV export.
     *
     * @param  array<string, mixed>  $filters
     *
     * @return Collection<int, User>
     */
    public function listForExport(array $filters = []): Collection
    {
        return $this->buildListQuery($filters)->get();
    }

    /**
     * Count candidates matching list filters (export guard).
     *
     * @param  array<string, mixed>  $filters
     */
    public function countForList(array $filters = []): int
    {
        return $this->buildListQuery($filters)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Builder<User>
     */
    public function buildListQuery(array $filters = []): Builder
    {
        $bucket = (string) ($filters['bucket'] ?? 'all');

        $query = User::query()->candidates();

        $this->applyListBucket($query, $bucket);

        if (!empty($filters['profile_status'])) {
            $query->where('profile_status', (string) $filters['profile_status']);
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', (string) $filters['gender']);
        }

        if (!empty($filters['marital_status'])) {
            $query->where('marital_status', (string) $filters['marital_status']);
        }

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

        $sort = (string) ($filters['sort'] ?? 'latest');

        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            'published_at' => $query->orderByDesc('published_at')->orderByDesc('updated_at'),
            default => $query->latest('updated_at'),
        };

        return $query;
    }

    /**
     * Create a candidate user (basic details). Section payloads use profile section endpoints.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId = null): User
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
        $data['status'] = $data['status'] ?? 'active';
        $data['profile_status'] = $data['profile_status'] ?? 'draft';

        if ($actorId !== null) {
            $data['created_by'] = $actorId;
            $data['updated_by'] = $actorId;
        }

        try {
            return DB::transaction(fn(): User => User::query()->create($data));
        } catch (\Throwable $e) {
            Log::error('admin.candidate.create_failed', [
                'actor_id' => $actorId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?int $actorId = null): User
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

        if ($actorId !== null) {
            $data['updated_by'] = $actorId;
        }

        try {
            return DB::transaction(function () use ($user, $data): User {
                $user->update($data);

                return $user->refresh();
            });
        } catch (\Throwable $e) {
            Log::error('admin.candidate.update_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Soft-delete a candidate and record the acting admin user.
     */
    public function delete(User $user, ?int $actorId = null): bool
    {
        try {
            return DB::transaction(function () use ($user, $actorId): bool {
                if ($actorId !== null) {
                    $user->forceFill(['deleted_by' => $actorId])->save();
                }

                return (bool) $user->delete();
            });
        } catch (\Throwable $e) {
            Log::error('admin.candidate.delete_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Restore a soft-deleted candidate profile.
     */
    public function restore(User $user, ?int $actorId = null): User
    {
        if (!$user->trashed()) {
            throw ValidationException::withMessages([
                'candidate' => ['Candidate is not deleted.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($user, $actorId): User {
                $user->restore();
                $user
                    ->forceFill([
                        'deleted_by' => null,
                        'updated_by' => $actorId,
                    ])
                    ->save();

                return $user->refresh();
            });
        } catch (\Throwable $e) {
            Log::error('admin.candidate.restore_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transition profile_status (published, under_review, suspended, spam, draft).
     */
    public function updateProfileStatus(User $user, string $profileStatus, ?int $actorId = null): User
    {
        if (!in_array($profileStatus, self::PROFILE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'profile_status' => ['Invalid profile status.'],
            ]);
        }

        $updates = [
            'profile_status' => $profileStatus,
        ];

        if ($profileStatus === 'published' && $user->published_at === null) {
            $updates['published_at'] = now();
        }

        if ($profileStatus === 'suspended') {
            $updates['status'] = 'suspended';
        } elseif ((string) ($user->status ?? '') === 'suspended' && $profileStatus !== 'suspended') {
            $updates['status'] = 'active';
        }

        if ($actorId !== null) {
            $updates['updated_by'] = $actorId;
        }

        try {
            return DB::transaction(function () use ($user, $updates): User {
                $user->update($updates);

                return $user->refresh();
            });
        } catch (\Throwable $e) {
            Log::error('admin.candidate.profile_status_failed', [
                'user_id' => $user->id,
                'profile_status' => $profileStatus,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyListBucket(Builder $query, string $bucket): void
    {
        match ($bucket) {
            'published' => $query->where('profile_status', 'published'),
            'under_review' => $query->where('profile_status', 'under_review'),
            'suspended' => $query->where('profile_status', 'suspended'),
            'spam' => $query->where('profile_status', 'spam'),
            'featured' => $query->where('is_featured', true)->where('profile_status', 'published'),
            'deleted' => $query->onlyTrashed(),
            default => null,
        };
    }

    private function candidateRoleId(): ?int
    {
        $roleId = Role::query()->where('name', 'candidate')->value('id');

        return $roleId !== null ? (int) $roleId : null;
    }
}
