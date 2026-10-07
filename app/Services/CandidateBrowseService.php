<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\ScoutConfig;
use App\Support\ViewerPreferredGender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CandidateBrowseService
{
    public function __construct(
        private readonly CandidateCardDataService $cardData,
        private readonly CandidateAlgoliaBrowseService $algoliaBrowse,
        private readonly CandidateDiscoveryExclusionService $exclusions
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateBrowse(User $viewer, int $perPage, array $filters = [], int $page = 1): LengthAwarePaginator
    {
        $filters = ViewerPreferredGender::mergeIntoFilters($viewer, $filters);

        if (ScoutConfig::usesAlgolia()) {
            return $this->algoliaBrowse->paginateBrowse($viewer, $perPage, max(1, $page), $filters);
        }

        return $this->paginatePublishedFromDatabase($perPage, $filters, $viewer, null);
    }

    /**
     * Guest browse: published candidates only; no preferred-gender merge, exclusions, or favorites.
     *
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatePublicBrowse(int $perPage, array $filters = [], int $page = 1): LengthAwarePaginator
    {
        if (ScoutConfig::usesAlgolia()) {
            return $this->algoliaBrowse->paginatePublicBrowse($perPage, max(1, $page), $filters);
        }

        return $this->paginatePublishedFromDatabase($perPage, $filters, null, max(1, $page));
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginatePublishedFromDatabase(
        int $perPage,
        array $filters,
        ?User $viewer,
        ?int $page
    ): LengthAwarePaginator {
        $query = User::query()
            ->candidates()
            ->where('profile_status', 'published')
            ->whereNotNull('published_at')
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($viewer instanceof User) {
            $query->where('id', '!=', $viewer->id);
            $excludedIds = $this->exclusions->excludedUserIdsForViewer($viewer);

            if ($excludedIds !== []) {
                $query->getQuery()->whereNotIn('id', $excludedIds);
            }
        }

        CandidateDiscoveryFilterApplier::apply($query, $filters, 'users');

        /** @var Paginator<int, User> $paginator */
        $paginator =
            $page === null ? $query->paginate($perPage) : $query->paginate($perPage, ['*'], 'page', $page);
        $payloads = $this->cardData->buildCardPayloads(
            $paginator->getCollection(),
            $viewer instanceof User ? $viewer->id : 0,
            $viewer instanceof User
        );
        // @phpstan-ignore argument.type (Collection<int, array> replaces User models)
        $paginator->setCollection(collect($payloads));

        /** @var LengthAwarePaginator<int, array<string, mixed>> $result */
        $result = $paginator;

        return $result;
    }
}
