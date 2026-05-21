<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\PaymentService;
use Illuminate\Validation\Rule;

class ListPaymentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.payments.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:255'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'package_id' => ['sometimes', 'integer', 'exists:packages,id'],
            'payment_status' => ['sometimes', 'string', Rule::in(PaymentService::PAYMENT_STATUSES)],
            'gateway_name' => ['sometimes', 'string', 'max:64'],
            'payment_method' => ['sometimes', 'string', Rule::in(PaymentService::PAYMENT_METHODS)],
            'paid_from' => ['sometimes', 'date'],
            'paid_to' => ['sometimes', 'date', 'after_or_equal:paid_from'],
            'sort' => ['sometimes', 'string', Rule::in(PaymentService::SORT_OPTIONS)],
        ];
    }
}
