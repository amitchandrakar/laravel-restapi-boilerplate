<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\PublicCandidateProfileService;
use Illuminate\Http\JsonResponse;

class PublicCandidateProfileController extends Controller
{
    public function __construct(private readonly PublicCandidateProfileService $publicProfiles) {}

    public function show(User $candidate): JsonResponse
    {
        if (!$this->publicProfiles->isPubliclyListable($candidate)) {
            return $this->notFoundResponse('Candidate not found');
        }

        return $this->successResponse(
            $this->publicProfiles->buildTeaser($candidate),
            'Candidate profile fetched successfully'
        );
    }
}
