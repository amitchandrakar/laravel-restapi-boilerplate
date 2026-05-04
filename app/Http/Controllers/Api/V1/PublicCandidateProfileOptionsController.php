<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\CandidateProfileOptionsService;
use Illuminate\Http\JsonResponse;

class PublicCandidateProfileOptionsController extends Controller
{
    public function __construct(private readonly CandidateProfileOptionsService $candidateProfileOptionsService) {}

    public function __invoke(): JsonResponse
    {
        return $this->successResponse(
            $this->candidateProfileOptionsService->all(),
            'Candidate profile options fetched successfully'
        );
    }
}
