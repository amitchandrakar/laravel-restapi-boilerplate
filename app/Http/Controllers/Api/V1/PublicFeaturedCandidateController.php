<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PublicFeaturedCandidateResource;
use App\Services\FeaturedCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFeaturedCandidateController extends Controller
{
    public function __construct(private readonly FeaturedCandidateService $featuredCandidateService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('perPage', 15)));
        $paginator = $this->featuredCandidateService->paginatePublicFeatured($perPage);

        return $this->paginatedResponse(
            PublicFeaturedCandidateResource::collection($paginator),
            'Featured candidates fetched successfully'
        );
    }
}
