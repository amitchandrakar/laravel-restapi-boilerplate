<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Support\QuerySearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminSubscriptionService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function listActive(array $filters = []): LengthAwarePaginator
    {
        $now = now();

        $query = $this->baseQuery($filters)
            ->where('subscriptions.subscription_status', 'active')
            ->where(static function (Builder $builder) use ($now): void {
                $builder->whereNull('subscriptions.ends_at')->orWhere('subscriptions.ends_at', '>', $now);
            });

        return $this->paginate($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function listExpiringSoon(array $filters = []): LengthAwarePaginator
    {
        $now = now();
        $until = $now->copy()->addDays(7);

        $query = $this->baseQuery($filters)
            ->where('subscriptions.subscription_status', 'active')
            ->whereNotNull('subscriptions.ends_at')
            ->whereBetween('subscriptions.ends_at', [$now, $until]);

        return $this->paginate($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function listExpired(array $filters = []): LengthAwarePaginator
    {
        $now = now();

        $query = $this->baseQuery($filters)->where(static function (Builder $builder) use ($now): void {
            $builder
                ->where('subscriptions.subscription_status', 'expired')
                ->orWhere(static function (Builder $inner) use ($now): void {
                    $inner->whereNotNull('subscriptions.ends_at')->where('subscriptions.ends_at', '<', $now);
                });
        });

        return $this->paginate($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function historyForUser(User $candidate, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters)
            ->where('subscriptions.user_id', $candidate->id)
            ->orderByDesc('subscriptions.id');

        return $this->paginate($query, $filters);
    }

    public function resolveCandidateByUuid(string $uuid): ?User
    {
        return User::query()->candidates()->where('uuid', $uuid)->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Builder<Subscription>
     */
    private function baseQuery(array $filters): Builder
    {
        $query = Subscription::query()->with(['user', 'package']);
        $query
            ->getQuery()
            ->select('subscriptions.*')
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->join('roles', 'users.role_id', '=', 'roles.id');
        $query->where('roles.name', 'candidate');

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            QuerySearch::whereContainsAny(
                $query,
                ['users.email', 'users.first_name', 'users.last_name', 'users.phone'],
                $search
            );
        }

        if (!empty($filters['package_id'])) {
            $query->where('subscriptions.package_id', (int) $filters['package_id']);
        }

        if (!empty($filters['ends_from'])) {
            $query->where('subscriptions.ends_at', '>=', Carbon::parse((string) $filters['ends_from'])->startOfDay());
        }

        if (!empty($filters['ends_to'])) {
            $query->where('subscriptions.ends_at', '<=', Carbon::parse((string) $filters['ends_to'])->endOfDay());
        }

        return $query;
    }

    /**
     * @param  Builder<Subscription>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySort(Builder $query, array $filters): void
    {
        $sort = (string) ($filters['sort'] ?? 'latest');
        $direction = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'oldest' => $query->getQuery()->orderBy('subscriptions.id', $direction),
            'candidate' => $query
                ->getQuery()
                ->orderBy('users.first_name', $direction)
                ->orderBy('users.last_name', $direction),
            'package' => $this->applyPackageSort($query, $direction),
            'starts' => $query->getQuery()->orderBy('subscriptions.started_at', $direction),
            'ends' => $query->getQuery()->orderBy('subscriptions.ends_at', $direction),
            'status' => $query->getQuery()->orderBy('subscriptions.subscription_status', $direction),
            default => $query->getQuery()->orderBy('subscriptions.id', 'desc'),
        };
    }

    /**
     * @param  Builder<Subscription>  $query
     */
    private function applyPackageSort(Builder $query, string $direction): void
    {
        if (!$this->queryHasJoin($query, 'packages')) {
            $query->getQuery()->join('packages', 'subscriptions.package_id', '=', 'packages.id');
        }

        $query->getQuery()->orderBy('packages.name', $direction);
    }

    /**
     * @param  Builder<Subscription>  $query
     */
    private function queryHasJoin(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins ?? [];

        foreach ($joins as $join) {
            if (is_string($join->table) && str_contains($join->table, $table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Builder<Subscription>  $query
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));
        $this->applySort($query, $filters);

        $paginator = $query->paginate($perPage);

        return $this->attachCandidateProfilePhotos($paginator);
    }

    /**
     * @param  LengthAwarePaginator<int, Subscription>  $paginator
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    private function attachCandidateProfilePhotos(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        /** @var Collection<int, Subscription> $items */
        $items = $paginator->getCollection();
        $userIds = array_values(
            $items
                ->map(static fn(Subscription $subscription): int => $subscription->user_id)
                ->filter(static fn(int $id): bool => $id > 0)
                ->unique()
                ->all()
        );

        $photoMap = $this->cardData->profileImageUrlByUserId($userIds);

        $paginator->setCollection(
            $items->map(static function (Subscription $subscription) use ($photoMap): Subscription {
                $userId = $subscription->user_id;
                $subscription->setAttribute('candidateProfilePhoto', $photoMap[$userId] ?? '');

                return $subscription;
            })
        );

        return $paginator;
    }
}
