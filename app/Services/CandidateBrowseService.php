<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\ScoutConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CandidateBrowseService
{
    public function __construct(
        private readonly CandidateCardDataService $cardData,
        private readonly CandidateAlgoliaBrowseService $algoliaBrowse
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateBrowse(User $viewer, int $perPage, array $filters = [], int $page = 1): LengthAwarePaginator
    {
        if (ScoutConfig::usesAlgolia()) {
            return $this->algoliaBrowse->paginateBrowse($viewer, $perPage, max(1, $page), $filters);
        }

        return $this->paginateBrowseFromDatabase($viewer, $perPage, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateBrowseFromDatabase(User $viewer, int $perPage, array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->candidates()
            ->where('id', '!=', $viewer->id)
            ->where('profile_status', 'published')
            ->whereNotNull('published_at')
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        CandidateDiscoveryFilterApplier::apply($query, $filters, 'users');

        /** @var Paginator<int, User> $paginator */
        $paginator = $query->paginate($perPage);
        $payloads = $this->cardData->buildCardPayloads($paginator->getCollection(), (int) $viewer->id, true);
        // @phpstan-ignore argument.type (Collection<int, array> replaces User models)
        $paginator->setCollection(collect($payloads));

        /** @var LengthAwarePaginator<int, array<string, mixed>> $result */
        $result = $paginator;

        return $result;
    }
}
