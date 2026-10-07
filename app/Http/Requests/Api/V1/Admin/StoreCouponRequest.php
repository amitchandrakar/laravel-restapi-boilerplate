<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:255'],
            'discountType' => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'discountValue' => ['required', 'integer', 'min:1'],
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
