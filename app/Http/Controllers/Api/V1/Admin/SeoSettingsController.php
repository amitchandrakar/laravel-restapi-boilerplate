<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSeoSettingsRequest;
use App\Services\SeoSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly SeoSettingsService $seoSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->seoSettingsService,
            'admin.settings.seo.view',
            'SEO settings fetched successfully'
        );
    }

    public function update(UpdateSeoSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->seoSettingsService,
            'admin.settings.seo.edit',
            'admin.settings.seo.update',
            $request->validated(),
            'SEO settings updated successfully'
        );
    }
}
