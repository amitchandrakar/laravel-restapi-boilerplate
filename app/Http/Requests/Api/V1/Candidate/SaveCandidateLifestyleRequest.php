<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateLifestyleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'diet' => ['nullable', 'string', 'max:64'],
            'smoking' => ['nullable', 'string', 'max:64'],
            'drinking' => ['nullable', 'string', 'max:64'],
        ];
    }
}
