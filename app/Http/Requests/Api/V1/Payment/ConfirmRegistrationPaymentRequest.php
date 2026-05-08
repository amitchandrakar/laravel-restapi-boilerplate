<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Http\Requests\Api\ApiFormRequest;

class ConfirmRegistrationPaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'razorpay_order_id' => 'required|string|max:255',
            'razorpay_payment_id' => 'required|string|max:255',
            'razorpay_signature' => 'required|string|max:512',
        ];
    }
}
