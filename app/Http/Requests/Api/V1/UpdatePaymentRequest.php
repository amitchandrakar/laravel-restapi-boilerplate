<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends ApiFormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'subscription_id' => ['sometimes', 'nullable', 'integer', 'exists:subscriptions,id'],
            'package_id' => ['sometimes', 'required', 'integer', 'exists:packages,id'],
            'gateway_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'gateway_order_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gateway_payment_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gateway_reference_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'payment_status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['pending', 'success', 'failed', 'refunded', 'cancelled']),
            ],
            'payment_method' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['upi', 'card', 'netbanking', 'wallet', 'cash', 'manual']),
            ],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'failed_reason' => ['sometimes', 'nullable', 'string'],
            'raw_response_json' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
