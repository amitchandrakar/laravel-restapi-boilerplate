<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Laravel\Scout\Builder;

class CandidateAlgoliaBrowseService
{
    public function __construct(private readonly CandidateCardDataService $cardData) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateBrowse(User $viewer, int $perPage, int $page, array $filters = []): LengthAwarePaginator
    {
        $built = CandidateAlgoliaFilterBuilder::build($filters, (string) $viewer->uuid);

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
        $payloads = $this->cardData->buildCardPayloads($paginator->getCollection(), (int) $viewer->id, true);
        // @phpstan-ignore argument.type (card payloads replace User models in paginator)
        $paginator->setCollection(collect($payloads));

        /** @var LengthAwarePaginator<int, array<string, mixed>> $result */
        $result = $paginator;

        return $result;
    }
}
