<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\ListCandidateDiscoveryRequest;
use App\Http\Requests\Api\V1\Candidate\ToggleCandidateFavoriteRequest;
use App\Http\Resources\Api\V1\CandidateCardResource;
use App\Http\Resources\Api\V1\CandidateMatchResource;
use App\Models\User;
use App\Services\CandidateBrowseService;
use App\Services\CandidateFavoriteService;
use App\Services\CandidateMatchService;
use App\Support\CandidateEntitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CandidateDiscoveryController extends Controller
{
    public function __construct(
        private readonly CandidateBrowseService $browseService,
        private readonly CandidateFavoriteService $favoriteService,
        private readonly CandidateMatchService $matchService
    ) {}

    public function browse(ListCandidateDiscoveryRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || !$user->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can browse profiles.');
        }

        if (!CandidateEntitlements::canBrowse($user)) {
            return $this->forbiddenResponse('You do not have permission to browse profiles.');
        }

        $perPage = (int) $request->validated('perPage', 15);
        $page = (int) $request->validated('page', 1);
        $paginator = $this->browseService->paginateBrowse($user, $perPage, $request->filters(), $page);

        return $this->paginatedResponse(
            CandidateCardResource::collection($paginator),
            'Candidates fetched successfully'
        );
    }

    public function favorites(ListCandidateDiscoveryRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || !$user->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can view favorites.');
        }

        if (!$user->can(CandidateEntitlements::MARK_FAVORITE)) {
            return $this->forbiddenResponse('You do not have permission to view favorites.');
        }

        $perPage = max(1, min(50, (int) $request->integer('perPage', 15)));
        $paginator = $this->favoriteService->paginateFavorites($user, $perPage, $request->filters());

        return $this->paginatedResponse(
            CandidateCardResource::collection($paginator),
            'Favorite candidates fetched successfully'
        );
    }

    public function toggleFavorite(ToggleCandidateFavoriteRequest $request, User $user): JsonResponse
    {
        $viewer = $request->user();

        if ($viewer === null || !$viewer->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can manage favorites.');
        }

        if (!$viewer->can(CandidateEntitlements::MARK_FAVORITE)) {
            return $this->forbiddenResponse('You do not have permission to favorite profiles.');
        }

        try {
            $this->favoriteService->toggle($viewer, $user, (bool) $request->validated('favorite'));
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->successResponse(
            ['favorite' => (bool) $request->validated('favorite'), 'candidateUuid' => $user->uuid],
            'Favorite updated successfully'
        );
    }

    public function matches(ListCandidateDiscoveryRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || !$user->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can view matches.');
        }

        if (!$user->can(CandidateEntitlements::VIEW_MATCHES)) {
            return $this->forbiddenResponse('You do not have permission to view matches.');
        }

        $perPage = (int) $request->validated('perPage', 15);
        $paginator = $this->matchService->paginateMatches($user, $perPage, $request->filters());

        return $this->paginatedResponse(CandidateMatchResource::collection($paginator), 'Matches fetched successfully');
    }
}
