<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSiteSettingsRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.site.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse($this->siteSettingsService->all(), 'Site settings fetched successfully');
    }

    public function update(UpdateSiteSettingsRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.site.edit')) {
            return $this->forbiddenResponse();
        }

        $updated = $this->siteSettingsService->update($request->validated());
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
            'admin.settings.site.update',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->successResponse($updated, 'Site settings updated successfully');
    }
}
