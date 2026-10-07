<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class PublicSiteSettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettingsService) {}

    public function show(): JsonResponse
    {
        return $this->successResponse(
            $this->siteSettingsService->cachedPublicApiArray(),
            'Site settings fetched successfully'
        );
    }
}
