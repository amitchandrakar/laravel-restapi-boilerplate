<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserVerificationDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class KycReviewResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly UserVerificationDocument $document) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = $this->document->verification_status;
        $approved = $status === 'approved';
        $kind = $approved ? 'kyc_approved' : 'kyc_rejected';
        $message = $approved
            ? 'Your identity verification was approved.'
            : 'Your identity verification needs attention. Please review and resubmit if required.';

        if (!$approved && filled($this->document->rejection_reason)) {
            $message = 'Your identity verification was not approved: ' . $this->document->rejection_reason;
        }

        return [
            'kind' => $kind,
            'user_uuid' => $this->document->user->uuid ?? '',
            'document_uuid' => $this->document->uuid,
            'document_type' => $this->document->document_type,
            'verification_status' => $status,
            'rejection_reason' => $this->document->rejection_reason,
            'deep_link' => '/account/verify-identity',
            'message' => $message,
        ];
    }
}
