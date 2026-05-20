<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PublicFeaturedCandidateResource;
use App\Models\User;
use App\Services\FeaturedCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class PublicFeaturedCandidateController extends Controller
{
    public function __construct(private readonly FeaturedCandidateService $featuredCandidateService) {}

    public function index(Request $request): JsonResponse
    {
        $viewer = $this->resolveViewerFromBearerTokenOrNull($request);

        $perPage = max(1, min(50, (int) $request->integer('perPage', 15)));
        $page = max(1, (int) $request->integer('page', 1));
        $paginator = $this->featuredCandidateService->paginatePublicFeatured($perPage, $page);

        if ($viewer instanceof User) {
            $candidateIds = collect($paginator->items())
                ->map(static fn($u): int => (int) data_get($u, 'id', 0))
                ->filter(static fn(int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($candidateIds !== []) {
                $matchScores = DB::table('matches')
                    ->where('user_id', $viewer->id)
                    ->where('match_status', 'active')
                    ->whereIn('matched_user_id', $candidateIds)
                    ->pluck('match_score', 'matched_user_id')
                    ->map(static fn($v): ?int => $v === null ? null : (int) $v)
                    ->all();

                $request->attributes->set('matchScoreByUserId', $matchScores);
            }
        }

        return $this->paginatedResponse(
            PublicFeaturedCandidateResource::collection($paginator),
            'Featured candidates fetched successfully'
        );
    }

    private function resolveViewerFromBearerTokenOrNull(Request $request): ?User
    {
        $token = (string) ($request->bearerToken() ?? '');
        if ($token === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken === null) {
            // Keep this endpoint public even when token is invalid.
            return null;
        }
        $user = $accessToken->tokenable;
        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }
}
