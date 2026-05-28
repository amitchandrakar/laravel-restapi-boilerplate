<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\LegalPageService;
use Illuminate\Http\JsonResponse;

class PublicLegalPageController extends Controller
{
    public function __construct(private readonly LegalPageService $legalPageService) {}

    public function show(string $slug): JsonResponse
    {
        $page = $this->legalPageService->findPublishedBySlug($slug);

        if ($page === null) {
            return $this->errorResponse('Legal page not found', 404);
        }

        return $this->successResponse(
            $this->legalPageService->toPublicApiArray($page),
            'Legal page fetched successfully'
        );
    }
}
