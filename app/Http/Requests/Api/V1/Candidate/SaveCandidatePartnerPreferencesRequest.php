<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidatePartnerPreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'preferred_min_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'preferred_max_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'preferred_min_height' => ['nullable', 'string', 'max:32'],
            'preferred_max_height' => ['nullable', 'string', 'max:32'],
            'preferred_education' => ['nullable', 'string', 'max:255'],
            'preferred_location' => ['nullable', 'string', 'max:255'],
            'preferred_communities' => ['nullable', 'string', 'max:500'],
            'preferred_caste' => ['nullable', 'string', 'max:255'],
            'preferred_hobbies' => ['nullable', 'string', 'max:500'],
            'preferred_interests' => ['nullable', 'string', 'max:500'],
            'preferred_likes' => ['nullable', 'string', 'max:500'],
            'preferred_dislikes' => ['nullable', 'string', 'max:500'],
            'preferred_other_criteria' => ['nullable', 'string', 'max:1000'],
            'additional_preferences' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
