<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ContactRequestReceivedNotification extends Notification
{
    public function __construct(private readonly ContactRequest $contactRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->contactRequest->loadMissing('fromUser');
        $from = $this->contactRequest->fromUser;
        $fromUser = $from instanceof User ? $from : null;
        $fromName = $fromUser !== null ? trim($fromUser->first_name . ' ' . $fromUser->last_name) : '';

        return [
            'kind' => 'contact_request_received',
            'contact_request_uuid' => $this->contactRequest->uuid,
            'from_user_uuid' => $fromUser?->uuid,
            'from_user_name' => $fromName !== '' ? $fromName : null,
            'request_message' => $this->contactRequest->request_message,
            'message' => $fromUser !== null
                    ? sprintf('%s requested your contact number.', $fromName)
                    : 'Someone requested your contact number.',
        ];
    }
}
