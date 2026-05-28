<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!($this->resource instanceof Payment)) {
            return [];
        }

        $payment = $this->resource;
        $user = null;

        if ($payment->relationLoaded('user') && $payment->user instanceof User) {
            $user = $payment->user;
        }

        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'userId' => $payment->user_id,
            'subscriptionId' => $payment->subscription_id,
            'packageId' => $payment->package_id,
            'packageName' => data_get($payment, 'package.name'),
            'candidate' => [
                'uuid' => $user?->uuid,
                'fullName' => trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')),
                'profilePhoto' => $user?->profile_photo_url,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
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
