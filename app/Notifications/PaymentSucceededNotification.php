<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Package;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

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
        $amount = (string) $this->payment->amount;
        $currency = (string) $this->payment->currency;

        return [
            'kind' => 'payment_succeeded',
            'payment_uuid' => $this->payment->uuid,
            'package_name' => $packageName,
            'amount' => $amount,
            'currency' => $currency,
            'message' => $packageName !== null
                    ? sprintf('Your payment of %s %s for %s was successful.', $currency, $amount, $packageName)
                    : sprintf('Your payment of %s %s was successful.', $currency, $amount),
        ];
    }
}
