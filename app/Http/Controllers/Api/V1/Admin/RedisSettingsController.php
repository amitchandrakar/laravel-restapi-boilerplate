<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateRedisSettingsRequest;
use App\Services\RedisSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedisSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly RedisSettingsService $redisSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->redisSettingsService,
            'admin.settings.redis.view',
            'Redis settings fetched successfully'
        );
    }

    public function update(UpdateRedisSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->redisSettingsService,
            'admin.settings.redis.edit',
            'admin.settings.redis.update',
            $request->validated(),
            'Redis settings updated successfully'
        );
    }
}
