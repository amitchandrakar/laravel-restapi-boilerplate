<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveAdminCandidateFullProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'candidate_uuid' => ['sometimes', 'nullable', 'uuid'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:255'],
            'basics' => ['sometimes', 'array'],
            'basics.profile_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photos' => ['sometimes', 'array'],
            'photos.photos' => ['sometimes', 'array', 'max:5'],
            'photos.photos.*' => ['required_with:photos.photos', 'url', 'max:2048'],
            'personal_details' => ['sometimes', 'array'],
            'horoscope' => ['sometimes', 'array'],
            'location_family_roots' => ['sometimes', 'array'],
            'career_education' => ['sometimes', 'array'],
            'family_background' => ['sometimes', 'array'],
            'lifestyle' => ['sometimes', 'array'],
            'partner_preferences' => ['sometimes', 'array'],
            'basics.email' => ['required_without:candidate_uuid', 'email', 'max:255'],
            'basics.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'basics.photo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'basics.religion' => ['sometimes', 'nullable', 'string', 'max:128'],
            'basics.caste' => ['sometimes', 'nullable', 'string', 'max:128'],
            'basics.sub_caste' => ['sometimes', 'nullable', 'string', 'max:128'],
            'basics.community' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }
}
