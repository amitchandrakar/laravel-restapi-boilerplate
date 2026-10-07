<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CandidateAlgoliaBrowseService
{
    public function __construct(
        private readonly CandidateCardDataService $cardData,
        private readonly CandidateDiscoveryExclusionService $exclusions
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateBrowse(User $viewer, int $perPage, int $page, array $filters = []): LengthAwarePaginator
    {
        $built = CandidateAlgoliaFilterBuilder::build(
            $filters,
            (string) $viewer->uuid,
            $this->exclusions->excludedUserUuidsForViewer($viewer)
        );

        return $this->paginateWithBuiltFilters($built, $perPage, $page, (int) $viewer->id, true);
    }

    /**
     * Guest Algolia browse: no viewer exclusions.
     *
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatePublicBrowse(int $perPage, int $page, array $filters = []): LengthAwarePaginator
    {
        $built = CandidateAlgoliaFilterBuilder::build($filters, '', []);

        return $this->paginateWithBuiltFilters($built, $perPage, $page, 0, false);
    }

    /**
     * @param  array{filters: string, numericFilters: list<string>}  $built
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateWithBuiltFilters(
        array $built,
        int $perPage,
        int $page,
        int $viewerId,
        bool $includeFavoriteFlag
    ): LengthAwarePaginator {
        /** @var Builder<User> $builder */
        $builder = User::search('');
        $options = [
            'filters' => $built['filters'],
        ];

        if ($built['numericFilters'] !== []) {
            $options['numericFilters'] = $built['numericFilters'];
        }
        $builder->options($options);
        $builder->orderBy('published_at', 'desc');

        /** @var Paginator<int, User> $paginator */
        $paginator = $builder->paginate($perPage, 'page', $page);
        $payloads = $this->cardData->buildCardPayloads($paginator->getCollection(), $viewerId, $includeFavoriteFlag);
        // @phpstan-ignore argument.type (card payloads replace User models in paginator)
        $paginator->setCollection(collect($payloads));

        /** @var LengthAwarePaginator<int, array<string, mixed>> $result */
        $result = $paginator;

        return $result;
    }
}
