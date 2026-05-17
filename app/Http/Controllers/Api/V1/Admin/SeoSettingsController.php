<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSeoSettingsRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Services\SeoSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoSettingsController extends Controller
{
    public function __construct(private readonly SeoSettingsService $seoSettingsService) {}

    public function update(UpdateSeoSettingsRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.seo.edit')) {
            return $this->forbiddenResponse();
        }

        $updated = $this->seoSettingsService->update($request->validated());

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'settings',
            0,
            'update',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.seo.update',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->successResponse($updated, 'SEO settings updated successfully');
    }

    public function show(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.seo.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse($this->seoSettingsService->all(), 'SEO settings fetched successfully');
    }
}
