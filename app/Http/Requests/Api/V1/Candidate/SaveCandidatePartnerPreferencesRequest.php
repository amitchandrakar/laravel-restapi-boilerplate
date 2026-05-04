<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SaveCandidatePartnerPreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return self::rulesWithPrefix('');
    }

    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public static function rulesWithPrefix(string $prefix): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.') . '.';

        return [
            $p . 'preferred_min_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            $p . 'preferred_max_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            $p . 'preferred_min_height' => ['nullable', 'string', 'max:32'],
            $p . 'preferred_max_height' => ['nullable', 'string', 'max:32'],
            $p . 'preferred_gender' => ['nullable', 'string', 'max:32'],
            $p . 'preferred_marital_status' => ['nullable', 'string', 'max:64'],
            $p . 'preferred_diet' => ['nullable', 'string', 'max:64'],
            $p . 'preferred_smoking' => ['nullable', 'string', 'max:32'],
            $p . 'preferred_drinking' => ['nullable', 'string', 'max:32'],
            $p . 'preferred_occupation' => ['nullable', 'string', 'max:255'],
            $p . 'preferred_caste' => ['nullable', 'string', 'max:255'],
            $p . 'preferred_income_min' => ['nullable', 'numeric', 'min:0'],
            $p . 'preferred_country_id' => [
                'nullable',
                'integer',
                Rule::exists('countries', 'id')->where('is_active', true),
            ],
            $p . 'preferred_state_id' => [
                'nullable',
                'integer',
                Rule::exists('states', 'id')->where('is_active', true),
            ],
            $p . 'preferred_city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            $p . 'preferred_language_id' => [
                'nullable',
                'integer',
                Rule::exists('languages', 'id')->where('is_active', true),
            ],
            $p . 'preferred_degree_ids' => ['nullable', 'array'],
            $p . 'preferred_degree_ids.*' => ['integer', Rule::exists('degrees', 'id')->where('is_active', true)],
            $p . 'preferred_location_ids' => ['nullable', 'array'],
            $p . 'preferred_location_ids.*' => ['integer', Rule::exists('cities', 'id')->where('is_active', true)],
            $p . 'preferred_community_ids' => ['nullable', 'array'],
            $p . 'preferred_community_ids.*' => ['integer', Rule::exists('surnames', 'id')->where('is_active', true)],
        ];
    }
}
