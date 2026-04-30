<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateLocationFamilyRootsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'current_country' => ['nullable', 'string', 'max:128'],
            'current_state' => ['nullable', 'string', 'max:128'],
            'current_city' => ['nullable', 'string', 'max:128'],
            'current_district' => ['nullable', 'string', 'max:128'],
            'current_village' => ['nullable', 'string', 'max:128'],
            'hometown_country' => ['nullable', 'string', 'max:128'],
            'hometown_state' => ['nullable', 'string', 'max:128'],
            'hometown_city' => ['nullable', 'string', 'max:128'],
            'hometown_district' => ['nullable', 'string', 'max:128'],
            'hometown_village' => ['nullable', 'string', 'max:128'],
            'maternal_country' => ['nullable', 'string', 'max:128'],
            'maternal_state' => ['nullable', 'string', 'max:128'],
            'maternal_city' => ['nullable', 'string', 'max:128'],
            'maternal_district' => ['nullable', 'string', 'max:128'],
            'maternal_village' => ['nullable', 'string', 'max:128'],
        ];
    }
}
