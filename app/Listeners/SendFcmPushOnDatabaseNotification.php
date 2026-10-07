<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\SendFcmPushForNotificationJob;
use App\Models\User;
use App\Services\MemberNotificationFeedService;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

class SendFcmPushOnDatabaseNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        if (!($notifiable instanceof User)) {
            return;
        }

        $toArray = [$event->notification, 'toArray'];

        if (!is_callable($toArray)) {
            return;
        }

        try {
            $result = $toArray($notifiable);
        } catch (Throwable) {
            return;
        }

        if (!is_array($result)) {
            return;
        }

        $data = $result;

        $kind = $data['kind'] ?? null;

        if (!is_string($kind) || !in_array($kind, MemberNotificationFeedService::FEED_KINDS, true)) {
            return;
        }

        SendFcmPushForNotificationJob::dispatch($notifiable->id, $data);
    }
}
