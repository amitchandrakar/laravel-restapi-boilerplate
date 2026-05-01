<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\StoreKycDocumentRequest;
use App\Http\Resources\Api\V1\KycDocumentResource;
use App\Services\KycDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateKycController extends Controller
{
    public function __construct(private readonly KycDocumentService $kycDocumentService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $docs = $user->verificationDocuments()->orderBy('document_type')->get();

        return $this->successResponse(
            KycDocumentResource::collection($docs)->resolve(),
            'KYC documents fetched successfully'
        );
    }

    public function upsert(StoreKycDocumentRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $doc = $this->kycDocumentService->upsertForCandidate($user, $request->validated());

        return $this->successResponse(
            KycDocumentResource::make($doc)->resolve(),
            'KYC document submitted successfully'
        );
    }
}
