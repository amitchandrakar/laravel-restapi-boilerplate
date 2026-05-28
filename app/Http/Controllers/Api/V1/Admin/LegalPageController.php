<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateLegalPageRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\LegalPage;
use App\Services\LegalPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LegalPageController extends Controller
{
    public function __construct(private readonly LegalPageService $legalPageService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.legal.view')) {
            return $this->forbiddenResponse();
        }

        try {
            $pages = $this->legalPageService
                ->list()
                ->map(fn(LegalPage $page): array => $this->legalPageService->toApiArray($page))
                ->values()
                ->all();

            return $this->successResponse($pages, 'Legal pages fetched successfully');
        } catch (Throwable $e) {
            Log::error('Failed to list legal pages', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch legal pages', 500);
        }
    }

    public function show(Request $request, LegalPage $legalPage): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.legal.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            $this->legalPageService->toApiArray($legalPage),
            'Legal page fetched successfully'
        );
    }

    public function update(UpdateLegalPageRequest $request, LegalPage $legalPage): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.legal.edit')) {
            return $this->forbiddenResponse();
        }

        try {
            $updated = $this->legalPageService->update(
                $legalPage,
                $request->validated(),
                $this->authenticatedUserId($request)
            );

            LogAuditJob::dispatch(
                $this->authenticatedUserId($request),
                'legal_page',
                $legalPage->id,
                'update',
                null,
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.settings.legal.update',
                'api_v1_admin',
                ['slug' => $legalPage->slug],
                $request->ip()
            );

            return $this->successResponse($updated, 'Legal page updated successfully');
        } catch (Throwable $e) {
            Log::error('Failed to update legal page', [
                'slug' => $legalPage->slug,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update legal page', 500);
        }
    }
}
