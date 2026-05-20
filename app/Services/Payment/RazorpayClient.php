<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayClient
{
    private Api $api;

    public function __construct()
    {
        $key = (string) config('services.razorpay.key_id', '');
        $secret = (string) config('services.razorpay.key_secret', '');
        $this->api = new Api($key, $secret);
    }

    /**
     * @param  array<string, string>  $notes
     *
     * @return array<string, mixed>
     */
    public function createOrder(int $amountPaise, string $currency, string $receipt, array $notes = []): array
    {
        /** @var Order $order */
        $order = $this->api->order->create([
            'amount' => $amountPaise,
            'currency' => strtoupper($currency),
            'receipt' => $receipt,
            'notes' => $notes,
        ]);

        return $order->toArray();
    }

    /**
     * @param  array<string, string>  $attributes  razorpay_order_id, razorpay_payment_id, razorpay_signature
     */
    public function verifyCheckoutSignature(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret', '');

        if ($secret === '') {
            return false;
        }

        try {
            $this->api->utility->verifyWebhookSignature($rawBody, $signatureHeader, $secret);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }
}
