<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ContactRequestAcceptedNotification extends Notification
{
    public function __construct(private readonly ContactRequest $contactRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->contactRequest->loadMissing('toUser');
        $to = $this->contactRequest->toUser;
        $toUser = $to instanceof User ? $to : null;
        $toName = $toUser !== null ? trim($toUser->first_name . ' ' . $toUser->last_name) : '';

        return [
            'kind' => 'contact_request_accepted',
            'contact_request_uuid' => $this->contactRequest->uuid,
            'to_user_uuid' => $toUser?->uuid,
            'to_user_name' => $toName !== '' ? $toName : null,
            'message' => $toUser !== null
                    ? sprintf('%s accepted your contact request. You can view their phone on their profile.', $toName)
                    : 'Your contact request was accepted.',
        ];
    }
}
