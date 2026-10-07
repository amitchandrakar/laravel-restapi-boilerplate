<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Coupon;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');
        $couponId = $coupon?->id;

        return [
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('coupons', 'code')->ignore($couponId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'discountType' => ['sometimes', 'string', Rule::in(['percent', 'fixed'])],
            'discountValue' => ['sometimes', 'integer', 'min:1'],
            'packageUuid' => ['nullable', 'string', 'uuid', Rule::exists('packages', 'uuid')->whereNull('deleted_at')],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }
}
