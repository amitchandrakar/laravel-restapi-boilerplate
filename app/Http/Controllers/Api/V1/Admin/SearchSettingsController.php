<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSearchSettingsRequest;
use App\Services\SearchSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly SearchSettingsService $searchSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->searchSettingsService,
            'admin.settings.search.view',
            'Search settings fetched successfully'
        );
    }

    public function update(UpdateSearchSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->searchSettingsService,
            'admin.settings.search.edit',
            'admin.settings.search.update',
            $request->validated(),
            'Search settings updated successfully'
        );
    }
}
