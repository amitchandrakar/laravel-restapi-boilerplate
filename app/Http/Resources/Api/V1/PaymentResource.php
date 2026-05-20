<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Payment $payment */
        $payment = $this->resource;

        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'userId' => $payment->user_id,
            'subscriptionId' => $payment->subscription_id,
            'packageId' => $payment->package_id,
            'gatewayName' => $payment->gateway_name,
            'gatewayOrderId' => $payment->gateway_order_id,
            'gatewayPaymentId' => $payment->gateway_payment_id,
            'gatewayReferenceId' => $payment->gateway_reference_id,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'paymentStatus' => $payment->payment_status,
            'paymentMethod' => $payment->payment_method,
            'paidAt' => $payment->paid_at,
            'failedReason' => $payment->failed_reason,
            'rawResponse' => $payment->raw_response_json,
            'createdAt' => $payment->created_at,
            'updatedAt' => $payment->updated_at,
        ];
    }
}
