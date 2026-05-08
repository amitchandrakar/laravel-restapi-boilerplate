<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Package;
use App\Models\Payment;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    public function __construct(private readonly Payment $payment, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->payment->loadMissing('package');
        $pkg = $this->payment->package;
        $packageName = $pkg instanceof Package ? (string) $pkg->name : null;

        return [
            'kind' => 'payment_failed',
            'payment_uuid' => $this->payment->uuid,
            'package_name' => $packageName,
            'reason' => $this->reason,
            'message' => $packageName !== null
                    ? sprintf('Payment for %s could not be completed: %s', $packageName, $this->reason)
                    : sprintf('Payment could not be completed: %s', $this->reason),
        ];
    }
}
