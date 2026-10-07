<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\CacheKeys;
use App\Support\ViewerPreferredGender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class FeaturedCandidateService
{
    public function __construct(private readonly CandidateDiscoveryExclusionService $exclusions) {}

    /**
     * @return Builder<User>
     */
    private function featuredCandidatesQuery(): Builder
    {
        $query = (new User())
            ->scopeCandidates(User::query())
            ->where('is_featured', true)
            ->where('profile_status', 'published')
            ->where('published_at', '!=', null);
        $query->getQuery()->orderBy('featured_at', 'desc')->orderBy('published_at', 'desc');

        return $query;
    }

    public function setFeatured(User $candidate, bool $isFeatured, int $actorId): User
    {
        if ($isFeatured) {
            if ((string) ($candidate->profile_status ?? '') !== 'published' || $candidate->published_at === null) {
                throw ValidationException::withMessages([
                    'isFeatured' => ['Only published candidate profiles can be featured.'],
                ]);
            }
            $candidate->forceFill([
                'is_featured' => true,
                'featured_at' => now(),
                'featured_by' => $actorId,
            ]);
        } else {
            $candidate->forceFill([
                'is_featured' => false,
                'featured_at' => null,
                'featured_by' => null,
            ]);
        }
        $candidate->save();

        $this->forgetFeaturedListCache();

        return $candidate->fresh();
    }

    private function forgetFeaturedListCache(): void
    {
        foreach (range(1, 5) as $page) {
            foreach ([15, 20, 50] as $perPage) {
                Cache::forget(CacheKeys::publicFeaturedPage($page, $perPage));
            }
        }
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginatePublicFeatured(int $perPage = 15, int $page = 1, ?User $viewer = null): LengthAwarePaginator
    {
        $page = max(1, $page);

        if ($viewer instanceof User) {
            $query = $this->featuredCandidatesQuery();
            $excludedIds = $this->exclusions->excludedUserIdsForViewer($viewer);

            if ($excludedIds !== []) {
                $query->getQuery()->whereNotIn('id', $excludedIds);
            }

            $preferredGender = ViewerPreferredGender::forUser($viewer);

            if ($preferredGender !== null) {
                $query->getQuery()->whereRaw('LOWER(TRIM(gender)) = ?', [$preferredGender]);
            }

            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        $ttl = max(60, (int) config('cache_strategy.featured_profiles_seconds', 300));
        $key = CacheKeys::publicFeaturedPage($page, $perPage);

        return Cache::remember(
            $key,
            $ttl,
            fn(): LengthAwarePaginator => $this->featuredCandidatesQuery()->paginate($perPage, ['*'], 'page', $page)
        );
    }
}
