<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\HoroscopeBirthPlaceValidator;
use Illuminate\Contracts\Validation\Validator;

class SaveAdminCandidateFullProfileRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $horoscope = $this->input('horoscope');

        if (is_array($horoscope) && isset($horoscope['time_of_birth']) && is_string($horoscope['time_of_birth'])) {
            $t = trim($horoscope['time_of_birth']);

            if ($t !== '' && preg_match('/^\d{2}:\d{2}$/', $t)) {
                $horoscope['time_of_birth'] = $t . ':00';
                $this->merge(['horoscope' => $horoscope]);
            }
        }

        $loc = $this->input('location_family_roots');

        if (!is_array($loc)) {
            return;
        }
        $legacyToId = [
            'maternal_country' => 'maternal_country_id',
            'maternal_state' => 'maternal_state_id',
            'maternal_city' => 'maternal_city_id',
        ];

        foreach ($legacyToId as $legacy => $canonical) {
            if (
                array_key_exists($legacy, $loc) &&
                $loc[$legacy] !== null &&
                $loc[$legacy] !== '' &&
                (!array_key_exists($canonical, $loc) || $loc[$canonical] === null || $loc[$canonical] === '')
            ) {
                $loc[$canonical] = $loc[$legacy];
            }
        }

        if (
            array_key_exists('maternal_village', $loc) &&
            $loc['maternal_village'] !== null &&
            $loc['maternal_village'] !== '' &&
            (!array_key_exists('maternal_village_name', $loc) ||
                $loc['maternal_village_name'] === null ||
                $loc['maternal_village_name'] === '') &&
            !is_numeric($loc['maternal_village'])
        ) {
            $v = $loc['maternal_village'];
            $loc['maternal_village_name'] = is_string($v) ? $v : (string) $v;
        }

        foreach (['maternal_country_id', 'maternal_state_id', 'maternal_city_id'] as $key) {
            if (!array_key_exists($key, $loc)) {
                continue;
            }
            $v = $loc[$key];

            if ($v === null || $v === '') {
                continue;
            }

            if (is_numeric($v)) {
                $loc[$key] = (int) $v;
            }
        }
        $this->merge(['location_family_roots' => $loc]);
    }

    public function rules(): array
    {
        return array_merge(
            [
                'candidate_uuid' => ['sometimes', 'nullable', 'uuid'],
                'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:255'],
                'basics' => ['sometimes', 'array'],
                'photos' => ['sometimes', 'array'],
                'photos.photos' => ['sometimes', 'array', 'max:5'],
                'photos.photos.*' => ['required_with:photos.photos', 'url', 'max:2048'],
                'personal_details' => ['sometimes', 'array'],
                'horoscope' => ['sometimes', 'array'],
                'location_family_roots' => ['sometimes', 'array'],
                'location_family_roots.maternal_village_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'career_education' => ['sometimes', 'array'],
                'family_background' => ['sometimes', 'array'],
                'lifestyle' => ['sometimes', 'array'],
                'partner_preferences' => ['sometimes', 'array'],
                'basics.email' => ['required_without:candidate_uuid', 'email', 'max:255'],
                'basics.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
                'basics.photo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
                'basics.sub_caste' => ['sometimes', 'nullable', 'string', 'max:128'],
            ],
            HoroscopeBirthPlaceValidator::rules('horoscope'),
            HoroscopeBirthPlaceValidator::flatGeoIdRules('maternal_', 'location_family_roots.'),
            SaveCandidateCareerEducationRequest::rulesWithPrefix('career_education'),
            SaveCandidateFamilyBackgroundRequest::rulesWithPrefix('family_background'),
            SaveCandidateLifestyleRequest::rulesWithPrefix('lifestyle'),
            SaveCandidatePartnerPreferencesRequest::rulesWithPrefix('partner_preferences')
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $horoscope = $this->input('horoscope');

            if (is_array($horoscope) && $horoscope !== []) {
                HoroscopeBirthPlaceValidator::validateConsistency($validator, $horoscope, 'horoscope');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $location = $this->input('location_family_roots');

            if (is_array($location) && $location !== []) {
                HoroscopeBirthPlaceValidator::validateGeoIdConsistency(
                    $validator,
                    $location,
                    'maternal_',
                    'location_family_roots'
                );
            }
        });
    }
}
