<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends ApiFormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'gateway_name' => ['nullable', 'string', 'max:64'],
            'gateway_order_id' => ['nullable', 'string', 'max:255'],
            'gateway_payment_id' => ['nullable', 'string', 'max:255'],
            'gateway_reference_id' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_status' => ['required', 'string', Rule::in(['pending', 'success', 'failed', 'refunded', 'cancelled'])],
            'payment_method' => ['nullable', 'string', Rule::in(['upi', 'card', 'netbanking', 'wallet', 'cash', 'manual'])],
            'paid_at' => ['nullable', 'date'],
            'failed_reason' => ['nullable', 'string'],
            'raw_response_json' => ['nullable', 'array'],
        ];
    }
}
