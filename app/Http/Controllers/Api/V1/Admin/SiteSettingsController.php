<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSiteSettingsRequest;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly SiteSettingsService $siteSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->siteSettingsService,
            'admin.settings.site.view',
            'Site settings fetched successfully'
        );
    }

    public function update(UpdateSiteSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->siteSettingsService,
            'admin.settings.site.edit',
            'admin.settings.site.update',
            $request->validated(),
            'Site settings updated successfully'
        );
    }
}
