<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Me;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class RegistrationCheckoutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'package_uuid' => [
                'required',
                'string',
                'uuid',
                Rule::exists('packages', 'uuid')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }
}
