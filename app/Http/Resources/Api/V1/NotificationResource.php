<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends JsonResource
{
    private static ?int $cachedUnreadCount = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->resource;

        $data = $notification->data;
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        if (self::$cachedUnreadCount === null) {
            $user = $request->user();
            self::$cachedUnreadCount = $user ? $user->unreadNotifications()->count() : 0;
        }

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $data,
            'readAt' => $notification->read_at?->toIso8601String(),
            'createdAt' => $notification->created_at?->toIso8601String(),
            'unreadNotificationsCount' => self::$cachedUnreadCount,
        ];
    }
}
