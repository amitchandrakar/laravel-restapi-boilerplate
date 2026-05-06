<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ListMemberNotificationsRequest;
use App\Models\User;
use App\Services\MemberNotificationFeedService;
use App\Support\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
class MemberNotificationController extends Controller
{
    public function __construct(private readonly MemberNotificationFeedService $feedService) {}

    public function index(ListMemberNotificationsRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->errorResponse('Unauthenticated', 401, ApiResponseBuilder::ERROR_UNAUTHORIZED);
        }

        $paginator = $this->feedService->paginateForUser(
            $user,
            $request->perPage(),
            $request->pageNumber(),
            $request->unreadOnly()
        );

        $unreadCount = $this->feedService->unreadCountForUser($user);
        $metaExtra = [
            'unreadCount' => $unreadCount,
            'pagination' => ApiResponseBuilder::paginationFromPaginator($paginator),
        ];

        return ApiResponseBuilder::success($paginator->items(), 'Notifications fetched successfully', 200, $metaExtra);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->errorResponse('Unauthenticated', 401, ApiResponseBuilder::ERROR_UNAUTHORIZED);
        }

        $count = $this->feedService->unreadCountForUser($user);

        return $this->successResponse(['unreadCount' => $count], 'Notification summary fetched successfully');
    }

    public function show(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->errorResponse('Unauthenticated', 401, ApiResponseBuilder::ERROR_UNAUTHORIZED);
        }

        $item = $this->feedService->findFeedItemForUser($user, $notificationId);
        if ($item === null) {
            return $this->notFoundResponse('Notification not found');
        }

        return $this->successResponse($item, 'Notification fetched successfully');
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->errorResponse('Unauthenticated', 401, ApiResponseBuilder::ERROR_UNAUTHORIZED);
        }

        $resolved = $this->resolveNotificationForUser($user, $notificationId);
        if ($resolved === null) {
            return $this->notFoundResponse('Notification not found');
        }
        if ($resolved === false) {
            return $this->forbiddenResponse('You cannot modify this notification.');
        }

        $resolved->forceFill(['read_at' => now()])->save();

        return $this->successResponse(null, 'Notification marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->errorResponse('Unauthenticated', 401, ApiResponseBuilder::ERROR_UNAUTHORIZED);
        }

        $user->notifications()
            ->whereNull('read_at')
            ->whereIn('data->kind', MemberNotificationFeedService::FEED_KINDS)
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read');
    }

    /**
     * @return DatabaseNotification|false|null null = not found, false = forbidden
     */
    private function resolveNotificationForUser(User $user, string $notificationId): DatabaseNotification|false|null
    {
        /** @var DatabaseNotification|null $notification */
        $notification = DatabaseNotification::query()->find($notificationId);
        if (!$notification instanceof DatabaseNotification) {
            return null;
        }
        if ((int) $notification->notifiable_id !== (int) $user->id) {
            return false;
        }
        if ($notification->notifiable_type !== User::class) {
            return false;
        }

        return $notification;
    }
}
