<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Support\QuerySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AdminSubscriptionService
{
    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function listActive(array $filters = []): LengthAwarePaginator
    {
        $now = now();

        $query = $this->baseQuery($filters)
            ->where('subscription_status', 'active')
            ->where(static function (Builder $builder) use ($now): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>', $now);
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
            ->where('subscription_status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$now, $until]);

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
                ->where('subscription_status', 'expired')
                ->orWhere(static function (Builder $inner) use ($now): void {
                    $inner->whereNotNull('ends_at')->where('ends_at', '<', $now);
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
        $query = $this->baseQuery($filters)->where('user_id', $candidate->id)->orderByDesc('id');

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
        $query = Subscription::query()
            ->with(['user', 'package'])
            ->whereHas('user', static function (Builder $builder): void {
                /** @var Builder<User> $builder */
                $builder->candidates();
            });

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereHas('user', static function (Builder $builder) use ($search): void {
                /** @var Builder<User> $builder */
                QuerySearch::whereContainsAny($builder, ['email', 'first_name', 'last_name', 'phone'], $search);
            });
        }

        if (!empty($filters['package_id'])) {
            $query->where('package_id', (int) $filters['package_id']);
        }

        if (!empty($filters['ends_from'])) {
            $query->where('ends_at', '>=', Carbon::parse((string) $filters['ends_from'])->startOfDay());
        }

        if (!empty($filters['ends_to'])) {
            $query->where('ends_at', '<=', Carbon::parse((string) $filters['ends_to'])->endOfDay());
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
            'oldest' => $query->orderBy('subscriptions.id', $direction),
            'candidate' => $query
                ->join('users', 'subscriptions.user_id', '=', 'users.id')
                ->orderBy('users.first_name', $direction)
                ->orderBy('users.last_name', $direction)
                ->select('subscriptions.*'),
            'package' => $query
                ->join('packages', 'subscriptions.package_id', '=', 'packages.id')
                ->orderBy('packages.name', $direction)
                ->select('subscriptions.*'),
            'starts' => $query->orderBy('subscriptions.started_at', $direction),
            'ends' => $query->orderBy('subscriptions.ends_at', $direction),
            'status' => $query->orderBy('subscriptions.subscription_status', $direction),
            default => $query->orderByDesc('subscriptions.id'),
        };
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

        return $query->paginate($perPage);
    }
}
