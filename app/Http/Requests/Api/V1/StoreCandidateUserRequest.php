<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreCandidateUserRequest extends StoreUserRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'phone' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
