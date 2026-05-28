<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationSettingsRequest;
use App\Services\NotificationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly NotificationSettingsService $notificationSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->notificationSettingsService,
            'admin.settings.notifications.view',
            'Notification settings fetched successfully'
        );
    }

    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->notificationSettingsService,
            'admin.settings.notifications.edit',
            'admin.settings.notifications.update',
            $request->validated(),
            'Notification settings updated successfully'
        );
    }
}
