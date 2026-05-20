<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CandidateFavoriteService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateFavorites(User $viewer, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Favorite::query()
            ->where('user_id', $viewer->id)
            ->whereNull('deleted_at')
            ->whereHas('favoriteUser', static function ($q) use ($filters): void {
                $q->whereNull('deleted_at');
                CandidateDiscoveryFilterApplier::apply($q, $filters, 'users');
            })
            ->with([
                'favoriteUser' => static function ($q): void {
                    $q->whereNull('deleted_at');
                },
            ])
            ->orderByDesc('created_at');

        /** @var Paginator<int, Favorite> $paginator */
        $paginator = $query->paginate($perPage);
        $users = $paginator
            ->getCollection()
            ->map(static function (Favorite $f): ?User {
                $u = $f->favoriteUser;

                return $u instanceof User ? $u : null;
            })
            ->filter(static fn(?User $u): bool => $u instanceof User);

        $payloads = $this->cardData->buildCardPayloads($users->values(), (int) $viewer->id, false);
        // Card payloads replace Favorite rows on the paginator for API resources.
        // @phpstan-ignore argument.type (Collection<int, array> replaces Favorite models)
        $paginator->setCollection(collect($payloads));

        /** @var LengthAwarePaginator<int, array<string, mixed>> $result */
        $result = $paginator;

        return $result;
    }

    public function toggle(User $viewer, User $target, bool $favorite): void
    {
        if ((int) $viewer->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'favorite' => ['You cannot favorite your own profile.'],
            ]);
        }

        if (!$target->hasRole('candidate')) {
            throw ValidationException::withMessages([
                'user' => ['Only candidate profiles can be favorited.'],
            ]);
        }

        if ((string) ($target->profile_status ?? '') !== 'published' || $target->published_at === null) {
            throw ValidationException::withMessages([
                'user' => ['Only published candidate profiles can be favorited.'],
            ]);
        }

        $existing = Favorite::withTrashed()
            ->where('user_id', $viewer->id)
            ->where('favorite_user_id', $target->id)
            ->first();

        if ($favorite) {
            if ($existing instanceof Favorite) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                return;
            }

            Favorite::query()->create([
                'user_id' => $viewer->id,
                'favorite_user_id' => $target->id,
                'source' => 'browse',
            ]);

            return;
        }

        if ($existing instanceof Favorite && !$existing->trashed()) {
            $existing->delete();
        }
    }
}
