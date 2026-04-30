<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateHoroscopeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date_of_birth' => ['nullable', 'date'],
            'time_of_birth' => ['nullable', 'date_format:H:i'],
            'zodiac_sign' => ['nullable', 'string', 'max:64'],
            'place_of_birth_line' => ['nullable', 'string', 'max:255'],
            'birth_country_id' => ['nullable', 'integer'],
            'birth_state_id' => ['nullable', 'integer'],
            'birth_city_id' => ['nullable', 'integer'],
            'birth_district_id' => ['nullable', 'integer'],
            'birth_village_id' => ['nullable', 'integer'],
        ];
    }
}
