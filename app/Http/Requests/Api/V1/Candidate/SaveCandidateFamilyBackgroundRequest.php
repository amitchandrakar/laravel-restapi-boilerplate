<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateFamilyBackgroundRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_gotra' => ['nullable', 'string', 'max:128'],
            'father_native_place' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_gotra' => ['nullable', 'string', 'max:128'],
            'mother_native_place' => ['nullable', 'string', 'max:255'],
            'brothers_count' => ['nullable', 'integer', 'min:0'],
            'sisters_count' => ['nullable', 'integer', 'min:0'],
            'family_type' => ['nullable', 'string', 'max:64'],
            'family_status' => ['nullable', 'string', 'max:64'],
        ];
    }
}
