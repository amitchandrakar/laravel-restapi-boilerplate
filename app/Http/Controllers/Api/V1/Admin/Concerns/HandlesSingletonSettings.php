<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Concerns;

use App\Http\Controllers\Api\V1\Controller;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @mixin Controller
 */
trait HandlesSingletonSettings
{
    protected function showSettings(
        Request $request,
        AbstractSingletonSettingsService $service,
        string $viewPermission,
        string $successMessage
    ): JsonResponse {
        if (!$request->user()?->can($viewPermission)) {
            return $this->forbiddenResponse();
        }

        try {
            return $this->successResponse($service->all(), $successMessage);
        } catch (Throwable $e) {
            Log::error('Failed to fetch admin settings', [
                'permission' => $viewPermission,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to fetch settings', 500);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function updateSettings(
        Request $request,
        AbstractSingletonSettingsService $service,
        string $editPermission,
        string $activityKey,
        array $validated,
        string $successMessage
    ): JsonResponse {
        if (!$request->user()?->can($editPermission)) {
            return $this->forbiddenResponse();
        }

        try {
            $updated = $service->update($validated, $this->authenticatedUserId($request));

            LogAuditJob::dispatch(
                $this->authenticatedUserId($request),
                'settings',
                0,
                'update',
                null,
                $validated,
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                $activityKey,
                'api_v1_admin',
                null,
                $request->ip()
            );

            return $this->successResponse($updated, $successMessage);
        } catch (Throwable $e) {
            Log::error('Failed to update admin settings', [
                'permission' => $editPermission,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update settings', 500);
        }
    }
}
