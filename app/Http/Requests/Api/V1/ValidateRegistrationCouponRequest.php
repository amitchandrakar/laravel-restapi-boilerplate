<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ValidateRegistrationCouponRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'packageUuid' => [
                'required',
                'string',
                'uuid',
                Rule::exists('packages', 'uuid')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'couponCode' => ['required', 'string', 'max:64'],
        ];
    }
}
