<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Identity check is enforced in controller (optional user_id/userId must match auth user)
            'user_id' => 'sometimes',
            'userId' => 'sometimes',

            'firstName' => 'sometimes|string|max:128',
            'lastName' => 'sometimes|string|max:128',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique($this->user()->getTable(), 'email')->ignore($this->user()->id),
            ],
            'phone' => 'sometimes|nullable|string|max:32',
        ];
    }
}
