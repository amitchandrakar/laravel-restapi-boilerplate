<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CandidateUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $photos = DB::table('user_images')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'image_type', 'image_url', 'thumbnail_url', 'is_profile_photo', 'sort_order'])
            ->map(static function (object $row): array {
                return [
                    'id' => (int) data_get($row, 'id'),
                    'type' => (string) data_get($row, 'image_type', ''),
                    'url' => (string) data_get($row, 'image_url', ''),
                    'thumbnailUrl' => data_get($row, 'thumbnail_url'),
                    'isProfilePhoto' => (bool) data_get($row, 'is_profile_photo', false),
                    'sortOrder' => (int) data_get($row, 'sort_order', 0),
                ];
            })
            ->values()
            ->all();

        $education = DB::table('user_education_details')
            ->where('user_id', $user->id)
            ->orderByDesc('is_highest')
            ->orderBy('start_year')
            ->get([
                'id',
                'degree_id',
                'field_of_study',
                'institution_name',
                'education_type',
                'start_year',
                'end_year',
                'grade_or_percentage',
                'is_highest',
            ])
            ->map(static function (object $row): array {
                return [
                    'id' => (int) data_get($row, 'id'),
                    'degreeId' => data_get($row, 'degree_id'),
                    'fieldOfStudy' => data_get($row, 'field_of_study'),
                    'institutionName' => data_get($row, 'institution_name'),
                    'educationType' => data_get($row, 'education_type'),
                    'startYear' => data_get($row, 'start_year'),
                    'endYear' => data_get($row, 'end_year'),
                    'gradeOrPercentage' => data_get($row, 'grade_or_percentage'),
                    'isHighest' => (bool) data_get($row, 'is_highest', false),
                ];
            })
            ->values()
            ->all();

        $partner = DB::table('user_partner_preferences')->where('user_id', $user->id)->first();

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'userType' => 'candidate',
            'roleId' => $user->role_id,
            'role' => data_get($user, 'primaryRole.name'),
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dateOfBirth' => $user->date_of_birth !== null ? (string) $user->date_of_birth : null,
            'maritalStatus' => data_get($user, 'marital_status'),
            'height' => $user->height,
            'currentCity' => $user->current_city,
            'education' => data_get($user, 'highest_education'),
            'occupation' => $user->occupation,
            'annualIncome' => data_get($user, 'annual_income'),
            'status' => $user->status,
            'profileStatus' => data_get($user, 'profile_status', 'draft'),
            'completedSections' => data_get($user, 'completed_sections_json', []),
            'publishedAt' => optional($user->published_at)->toDateTimeString(),
            'sections' => [
                'basics' => [
                    'profileSlug' => data_get($user, 'profile_slug'),
                    'age' => $user->date_of_birth !== null ? now()->diffInYears($user->date_of_birth) : null,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'photoUrl' => data_get($user, 'profile_photo_url'),
                    'religion' => data_get($user, 'religion'),
                    'caste' => data_get($user, 'caste'),
                    'subCaste' => data_get($user, 'sub_caste'),
                    'community' => data_get($user, 'community'),
                ],
                'photos' => $photos,
                'personalDetails' => [
                    'firstName' => $user->first_name,
                    'middleName' => data_get($user, 'middle_name'),
                    'lastName' => $user->last_name,
                    'gender' => $user->gender,
                    'bodyType' => data_get($user, 'body_type'),
                    'complexion' => data_get($user, 'complexion'),
                    'height' => $user->height,
                    'weight' => data_get($user, 'weight'),
                    'bloodGroup' => data_get($user, 'blood_group'),
                    'manglikStatus' => data_get($user, 'manglik_status'),
                    'aboutMe' => data_get($user, 'about_me'),
                ],
                'horoscopeDetails' => [
                    'dateOfBirth' => $user->date_of_birth !== null ? (string) $user->date_of_birth : null,
                    'timeOfBirth' => data_get($user, 'time_of_birth'),
                    'zodiacSign' => data_get($user, 'zodiac_sign'),
                    'placeOfBirthLine' => data_get($user, 'place_of_birth_line'),
                    'birthCountry' => data_get($user, 'place_of_birth_country'),
                    'birthState' => data_get($user, 'place_of_birth_state'),
                    'birthCity' => data_get($user, 'place_of_birth_city'),
                    'birthDistrict' => data_get($user, 'place_of_birth_district'),
                    'birthVillage' => data_get($user, 'place_of_birth_village'),
                ],
                'locationFamilyRoots' => [
                    'current' => [
                        'country' => data_get($user, 'current_country'),
                        'state' => data_get($user, 'current_state'),
                        'city' => data_get($user, 'current_city'),
                        'district' => data_get($user, 'current_district'),
                        'village' => data_get($user, 'current_village'),
                    ],
                    'hometown' => [
                        'country' => data_get($user, 'hometown_country'),
                        'state' => data_get($user, 'hometown_state'),
                        'city' => data_get($user, 'hometown_city'),
                        'district' => data_get($user, 'hometown_district'),
                        'village' => data_get($user, 'hometown_village'),
                    ],
                    'maternal' => [
                        'country' => data_get($user, 'maternal_country'),
                        'state' => data_get($user, 'maternal_state'),
                        'city' => data_get($user, 'maternal_city'),
                        'district' => data_get($user, 'maternal_district'),
                        'village' => data_get($user, 'maternal_village'),
                    ],
                ],
                'careerEducation' => [
                    'occupation' => data_get($user, 'occupation'),
                    'employer' => data_get($user, 'employer'),
                    'income' => data_get($user, 'income'),
                    'maritalStatus' => data_get($user, 'marital_status'),
                    'qualifications' => $education,
                ],
                'familyBackground' => [
                    'fatherName' => data_get($user, 'father_name'),
                    'fatherOccupation' => data_get($user, 'father_occupation'),
                    'fatherGotra' => data_get($user, 'father_gotra'),
                    'fatherNativePlace' => data_get($user, 'father_native_place'),
                    'motherName' => data_get($user, 'mother_name'),
                    'motherOccupation' => data_get($user, 'mother_occupation'),
                    'motherGotra' => data_get($user, 'mother_gotra'),
                    'motherNativePlace' => data_get($user, 'mother_native_place'),
                    'brothersCount' => data_get($user, 'brothers_count'),
                    'sistersCount' => data_get($user, 'sisters_count'),
                    'familyType' => data_get($user, 'family_type'),
                    'familyStatus' => data_get($user, 'family_status'),
                ],
                'lifestyle' => [
                    'diet' => data_get($user, 'diet'),
                    'smoking' => data_get($user, 'smoking'),
                    'drinking' => data_get($user, 'drinking'),
                    'hobbies' => data_get($user, 'hobbies'),
                    'interests' => data_get($user, 'interests'),
                    'likes' => data_get($user, 'likes'),
                    'dislikes' => data_get($user, 'dislikes'),
                ],
                'partnerPreferences' => [
                    'preferredMinAge' => data_get($partner, 'preferred_min_age'),
                    'preferredMaxAge' => data_get($partner, 'preferred_max_age'),
                    'preferredMinHeight' => data_get($partner, 'preferred_min_height'),
                    'preferredMaxHeight' => data_get($partner, 'preferred_max_height'),
                    'preferredEducation' => data_get($partner, 'preferred_education'),
                    'preferredOccupation' => data_get($partner, 'preferred_occupation'),
                    'preferredCommunity' => data_get($partner, 'preferred_community'),
                    'preferredLocation' => [
                        'countryId' => data_get($partner, 'preferred_country_id'),
                        'stateId' => data_get($partner, 'preferred_state_id'),
                        'cityId' => data_get($partner, 'preferred_city_id'),
                    ],
                    'preferredOtherCriteria' => data_get($partner, 'preferred_other_criteria'),
                ],
            ],
        ];
    }
}
