<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\ListCandidateKycDocumentsRequest;
use App\Http\Requests\Api\V1\Admin\ReviewKycDocumentRequest;
use App\Http\Resources\Api\V1\AdminPendingKycDocumentResource;
use App\Http\Resources\Api\V1\KycDocumentResource;
use App\Models\User;
use App\Models\UserVerificationDocument;
use App\Services\KycDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycDocumentController extends Controller
{
    public function __construct(private readonly KycDocumentService $kycDocumentService) {}

    public function pending(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = max(1, min(50, (int) $request->integer('perPage', 15)));
        $paginator = $this->kycDocumentService->paginatePendingForAdmin($perPage);

        return $this->paginatedResponse(
            AdminPendingKycDocumentResource::collection($paginator),
            'Pending KYC documents fetched successfully'
        );
    }

    /**
     * List all KYC documents for one candidate (any verification status).
     */
    public function indexForCandidate(ListCandidateKycDocumentsRequest $_request, User $user): JsonResponse
    {
        if (!$user->hasRole('candidate')) {
            return $this->notFoundResponse('Candidate not found');
        }

        $documents = $this->kycDocumentService->listForCandidate($user);

        return $this->successResponse(
            KycDocumentResource::collection($documents)->resolve(),
            'KYC documents fetched successfully'
        );
    }

    public function review(ReviewKycDocumentRequest $request, UserVerificationDocument $document): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }

        $updated = $this->kycDocumentService->reviewDocument(
            $document,
            (int) $request->user()->id,
            $request->validated()
        );

        return $this->successResponse(
            KycDocumentResource::make($updated)->resolve(),
            'KYC document updated successfully'
        );
    }
}
