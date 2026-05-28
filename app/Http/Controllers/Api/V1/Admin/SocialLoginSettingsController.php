<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateSocialLoginSettingsRequest;
use App\Services\SocialLoginSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialLoginSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly SocialLoginSettingsService $socialLoginSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->socialLoginSettingsService,
            'admin.settings.social.view',
            'Social login settings fetched successfully'
        );
    }

    public function update(UpdateSocialLoginSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->socialLoginSettingsService,
            'admin.settings.social.edit',
            'admin.settings.social.update',
            $request->validated(),
            'Social login settings updated successfully'
        );
    }
}
