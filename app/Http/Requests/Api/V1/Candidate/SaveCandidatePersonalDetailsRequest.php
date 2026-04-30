<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidatePersonalDetailsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:32'],
            'body_type' => ['nullable', 'string', 'max:64'],
            'complexion' => ['nullable', 'string', 'max:64'],
            'height' => ['nullable', 'string', 'max:32'],
            'blood_group' => ['nullable', 'string', 'max:16'],
            'manglik_status' => ['nullable', 'string', 'max:32'],
            'about_me' => ['nullable', 'string', 'max:500'],
        ];
    }
}
