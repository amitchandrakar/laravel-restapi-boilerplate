<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateStorageSettingsRequest;
use App\Services\StorageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly StorageSettingsService $storageSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->storageSettingsService,
            'admin.settings.storage.view',
            'Storage settings fetched successfully'
        );
    }

    public function update(UpdateStorageSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->storageSettingsService,
            'admin.settings.storage.edit',
            'admin.settings.storage.update',
            $request->validated(),
            'Storage settings updated successfully'
        );
    }
}
