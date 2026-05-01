<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSocialLoginSettingsRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Services\SocialLoginSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialLoginSettingsController extends Controller
{
    public function __construct(private readonly SocialLoginSettingsService $socialLoginSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.social.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            $this->socialLoginSettingsService->all(),
            'Social login settings fetched successfully'
        );
    }

    public function update(UpdateSocialLoginSettingsRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.social.edit')) {
            return $this->forbiddenResponse();
        }

        $updated = $this->socialLoginSettingsService->update($request->validated());
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
            'admin.settings.social.update',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->successResponse($updated, 'Social login settings updated successfully');
    }
}
