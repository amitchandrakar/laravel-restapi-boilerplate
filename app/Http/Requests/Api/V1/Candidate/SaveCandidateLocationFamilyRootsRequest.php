<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\HoroscopeBirthPlaceValidator;
use Illuminate\Contracts\Validation\Validator;

class SaveCandidateLocationFamilyRootsRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $legacyToId = [
            'maternal_country' => 'maternal_country_id',
            'maternal_state' => 'maternal_state_id',
            'maternal_city' => 'maternal_city_id',
            'maternal_district' => 'maternal_district_id',
            'maternal_village' => 'maternal_village_id',
        ];
        $merge = [];
        foreach ($legacyToId as $legacy => $canonical) {
            if ($this->filled($legacy) && !$this->filled($canonical)) {
                $merge[$canonical] = $this->input($legacy);
            }
        }
        foreach (
            [
                'maternal_country_id',
                'maternal_state_id',
                'maternal_city_id',
                'maternal_district_id',
                'maternal_village_id',
            ] as $key
        ) {
            if (!$this->has($key)) {
                continue;
            }
            $v = $this->input($key);
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $merge[$key] = (int) $v;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return array_merge(
            [
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
            ],
            HoroscopeBirthPlaceValidator::flatGeoIdRules('maternal_', '')
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            HoroscopeBirthPlaceValidator::validateGeoIdConsistency($validator, $this->all(), 'maternal_', '');
        });
    }
}
