<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\NotificationResource;
use App\Support\ApiResponseBuilder;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * If the request includes user_id or userId, ensure it matches the authenticated user.
     * Returns a 403 JsonResponse when it does not match, or null when no check or match.
     */
    private function validateRequestedUserId(Request $request): ?JsonResponse
    {
        $requested = $request->input('user_id') ?? $request->input('userId');
        if ($requested === null) {
            return null;
        }
        $user = $request->user();
        $matches = is_numeric($requested)
            ? (int) $requested === (int) $user->id
            : (string) $requested === (string) $user->uuid;
        if (!$matches) {
            return $this->errorResponse('Forbidden', 403);
        }

        return null;
    }

    /**
     * List notifications for the authenticated user (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $forbidden = $this->validateRequestedUserId($request);
        if ($forbidden !== null) {
            return $forbidden;
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage >= 1 && $perPage <= 100 ? $perPage : 15;

        /** @var LengthAwarePaginator $paginator */
        $paginator = $request->user()->notifications()->orderByDesc('created_at')->paginate($perPage);

        return $this->paginatedResponse(NotificationResource::collection($paginator), 'Notifications retrieved');
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $forbidden = $this->validateRequestedUserId($request);
        if ($forbidden !== null) {
            return $forbidden;
        }

        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification === null) {
            return $this->errorResponse('Notification not found', 404, ApiResponseBuilder::ERROR_NOT_FOUND);
        }

        $notification->markAsRead();

        return $this->successResponse(NotificationResource::make($notification), 'Notification marked as read');
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $forbidden = $this->validateRequestedUserId($request);
        if ($forbidden !== null) {
            return $forbidden;
        }

        $request->user()->unreadNotifications->markAsRead();

        return $this->successResponse(null, 'All notifications marked as read');
    }
}
