<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        return $candidate->fresh();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginatePublicFeatured(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->candidates()
            ->where('is_featured', true)
            ->where('profile_status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('featured_at')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }
}
