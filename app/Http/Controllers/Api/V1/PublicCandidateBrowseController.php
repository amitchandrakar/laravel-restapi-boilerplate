<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\ListCandidateDiscoveryRequest;
use App\Http\Resources\Api\V1\PublicCandidateCardResource;
use App\Services\CandidateBrowseService;
use Illuminate\Http\JsonResponse;

class PublicCandidateBrowseController extends Controller
{
    public function __construct(private readonly CandidateBrowseService $browseService) {}

    public function search(ListCandidateDiscoveryRequest $request): JsonResponse
    {
        $perPage = (int) $request->validated('perPage', 15);
        $page = (int) $request->validated('page', 1);
        $paginator = $this->browseService->paginatePublicBrowse($perPage, $request->filters(), $page);

        return $this->paginatedResponse(
            PublicCandidateCardResource::collection($paginator),
            'Candidates fetched successfully'
        );
    }
}
