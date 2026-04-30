<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateCareerEducationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'occupation' => ['nullable', 'string', 'max:128'],
            'employer' => ['nullable', 'string', 'max:255'],
            'income' => ['nullable', 'numeric', 'min:0'],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*.degree' => ['nullable', 'string', 'max:255'],
            'qualifications.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'qualifications.*.institution_name' => ['nullable', 'string', 'max:255'],
            'qualifications.*.year_of_graduation' => ['nullable', 'integer', 'min:1950', 'max:2100'],
        ];
    }
}
