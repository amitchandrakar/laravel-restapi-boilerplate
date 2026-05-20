<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\CacheKeys;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class FeaturedCandidateService
{
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
    public function paginatePublicFeatured(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $ttl = max(60, (int) config('cache_strategy.featured_profiles_seconds', 300));
        $page = max(1, $page);
        $key = CacheKeys::publicFeaturedPage($page, $perPage);

        return Cache::remember(
            $key,
            $ttl,
            fn(): LengthAwarePaginator => User::query()
                ->candidates()
                ->where('is_featured', true)
                ->where('profile_status', 'published')
                ->whereNotNull('published_at')
                ->orderByDesc('featured_at')
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'page', $page)
        );
    }
}
