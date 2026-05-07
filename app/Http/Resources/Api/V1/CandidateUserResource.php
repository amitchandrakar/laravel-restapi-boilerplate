<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Support\UserProfilePhotos;
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
        $photos = UserProfilePhotos::listForUser($user);
        $defaultPhotoUrl = (string) config('custom.image.profile_default', '/images/Coming-Soon.png');
        $profilePhotoFromGallery = collect($photos)->first(
            static fn(array $photo): bool => (bool) data_get($photo, 'isProfilePhoto', false)
        );
        if (!is_array($profilePhotoFromGallery)) {
            $profilePhotoFromGallery = $photos[0] ?? null;
        }
        $photoUrl = data_get($user, 'profile_photo_url');
        if (!is_string($photoUrl) || $photoUrl === '') {
            $photoUrl = is_array($profilePhotoFromGallery)
                ? (string) data_get($profilePhotoFromGallery, 'url', '')
                : '';
        }
        if ($photoUrl === '') {
            $photoUrl = $defaultPhotoUrl;
        }

        $profileIconUrl = is_array($profilePhotoFromGallery) ? data_get($profilePhotoFromGallery, 'iconUrl') : null;
        if (!is_string($profileIconUrl) || $profileIconUrl === '') {
            $profileIconUrl = null;
        }

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

        $siblings = DB::table('user_siblings_details')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'gender',
                'relation_type',
                'marital_status',
                'occupation',
                'education',
                'age',
                'is_elder',
                'sort_order',
            ])
            ->map(static function (object $row): array {
                return [
                    'id' => (int) data_get($row, 'id'),
                    'name' => (string) data_get($row, 'name', ''),
                    'gender' => data_get($row, 'gender'),
                    'relationType' => data_get($row, 'relation_type'),
                    'maritalStatus' => data_get($row, 'marital_status'),
                    'occupation' => data_get($row, 'occupation'),
                    'education' => data_get($row, 'education'),
                    'age' => data_get($row, 'age'),
                    'isElder' => (bool) data_get($row, 'is_elder', false),
                    'sortOrder' => (int) data_get($row, 'sort_order', 0),
                ];
            })
            ->values()
            ->all();

        $partner = DB::table('user_partner_preferences')->where('user_id', $user->id)->first();

        $partnerDegreeIds = self::decodeStoredIdList(
            $partner !== null ? data_get($partner, 'preferred_degree_ids') : null
        );
        $partnerLocationIds = self::decodeStoredIdList(
            $partner !== null ? data_get($partner, 'preferred_location_ids') : null
        );
        $partnerCommunityIds = self::decodeStoredIdList(
            $partner !== null ? data_get($partner, 'preferred_community_ids') : null
        );
        $preferredInterests = self::decodeStoredStringList(
            $partner !== null ? data_get($partner, 'preferred_interests') : null
        );
        $preferredMovieGenres = self::decodeStoredStringList(
            $partner !== null ? data_get($partner, 'preferred_movie_genres') : null
        );
        $preferredHobbies = self::decodeStoredStringList($partner !== null ? data_get($partner, 'preferred_hobbies') : null);
        $preferredLikes = self::decodeStoredStringList($partner !== null ? data_get($partner, 'preferred_likes') : null);
        $preferredDislikes = self::decodeStoredStringList(
            $partner !== null ? data_get($partner, 'preferred_dislikes') : null
        );

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'profileIconUrl' => $profileIconUrl,
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
            'isFeatured' => (bool) ($user->is_featured ?? false),
            'featuredAt' => optional($user->featured_at)->toDateTimeString(),
            'sections' => [
                'photos' => $photos,
                'personalDetails' => [
                    'firstName' => $user->first_name,
                    'middleName' => data_get($user, 'middle_name'),
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'photoUrl' => $photoUrl,
                    'age' => $user->date_of_birth !== null ? $user->date_of_birth->age : null,
                    'religion' => data_get($user, 'religion'),
                    'caste' => data_get($user, 'caste'),
                    'subCaste' => data_get($user, 'sub_caste'),
                    'community' => data_get($user, 'community'),
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
                    'birthCountryId' => data_get($user, 'birth_country_id'),
                    'birthStateId' => data_get($user, 'birth_state_id'),
                    'birthCityId' => data_get($user, 'birth_city_id'),
                    'birthDistrictId' => data_get($user, 'birth_district_id'),
                    'birthVillageId' => data_get($user, 'birth_village_id'),
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
                        'countryId' => data_get($user, 'maternal_country_id'),
                        'stateId' => data_get($user, 'maternal_state_id'),
                        'cityId' => data_get($user, 'maternal_city_id'),
                        'districtId' => data_get($user, 'maternal_district_id'),
                        'villageId' => data_get($user, 'maternal_village_id'),
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
                    'siblings' => $siblings,
                ],
                'lifestyle' => [
                    'diet' => data_get($user, 'diet'),
                    'smoking' => data_get($user, 'smoking'),
                    'drinking' => data_get($user, 'drinking'),
                    'sleepPattern' => data_get($user, 'sleep_pattern'),
                    'workingHours' => data_get($user, 'working_hours'),
                    'socialPersonality' => data_get($user, 'social_personality'),
                    'dietaryPreferences' => data_get($user, 'dietary_preferences'),
                    'drinkingHabits' => data_get($user, 'drinking_habits'),
                    'smokingHabits' => data_get($user, 'smoking_habits'),
                    'fitnessLevel' => data_get($user, 'fitness_level'),
                    'travelStyle' => data_get($user, 'travel_style'),
                    'communicationStyle' => data_get($user, 'communication_style'),
                    'relationshipWithFamily' => data_get($user, 'relationship_with_family'),
                    'weekendPreference' => data_get($user, 'weekend_preference'),
                    'interests' => data_get($user, 'interests', []),
                    'movieGenres' => data_get($user, 'movie_genres', []),
                    'hobbies' => data_get($user, 'hobbies', []),
                    'likes' => data_get($user, 'likes', []),
                    'dislikes' => data_get($user, 'dislikes', []),
                ],
                'partnerPreferences' => [
                    'preferredMinAge' => data_get($partner, 'preferred_min_age'),
                    'preferredMaxAge' => data_get($partner, 'preferred_max_age'),
                    'preferredMinHeight' => data_get($partner, 'preferred_min_height'),
                    'preferredMaxHeight' => data_get($partner, 'preferred_max_height'),
                    'preferredGender' => data_get($partner, 'preferred_gender'),
                    'preferredMaritalStatus' => data_get($partner, 'preferred_marital_status'),
                    'preferredDiet' => data_get($partner, 'preferred_diet'),
                    'preferredSmoking' => data_get($partner, 'preferred_smoking'),
                    'preferredDrinking' => data_get($partner, 'preferred_drinking'),
                    'preferredCaste' => data_get($partner, 'preferred_caste'),
                    'preferredIncomeMin' => data_get($partner, 'preferred_income_min'),
                    'preferredDegreeIds' => $partnerDegreeIds,
                    'preferredLocationIds' => $partnerLocationIds,
                    'preferredCommunityIds' => $partnerCommunityIds,
                    'preferredOccupation' => data_get($partner, 'preferred_occupation'),
                    'preferredSleepPattern' => data_get($partner, 'preferred_sleep_pattern'),
                    'preferredWorkingHours' => data_get($partner, 'preferred_working_hours'),
                    'preferredSocialPersonality' => data_get($partner, 'preferred_social_personality'),
                    'preferredDietaryPreferences' => data_get($partner, 'preferred_dietary_preferences'),
                    'preferredDrinkingHabits' => data_get($partner, 'preferred_drinking_habits'),
                    'preferredSmokingHabits' => data_get($partner, 'preferred_smoking_habits'),
                    'preferredFitnessLevel' => data_get($partner, 'preferred_fitness_level'),
                    'preferredTravelStyle' => data_get($partner, 'preferred_travel_style'),
                    'preferredCommunicationStyle' => data_get($partner, 'preferred_communication_style'),
                    'preferredRelationshipWithFamily' => data_get($partner, 'preferred_relationship_with_family'),
                    'preferredWeekendPreference' => data_get($partner, 'preferred_weekend_preference'),
                    'preferredInterests' => $preferredInterests,
                    'preferredMovieGenres' => $preferredMovieGenres,
                    'preferredHobbies' => $preferredHobbies,
                    'preferredLikes' => $preferredLikes,
                    'preferredDislikes' => $preferredDislikes,
                ],
            ],
        ];
    }

    /**
     * @param  mixed  $value  JSON column from the query builder (string or array depending on driver)
     * @return list<string>
     */
    private static function decodeStoredStringList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_map(static fn($v): string => (string) $v, $value));
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values(array_map(static fn($v): string => (string) $v, $decoded)) : [];
        }

        return [];
    }

    /**
     * @param  mixed  $value  JSON column from the query builder (string or array depending on driver)
     * @return list<int>
     */
    private static function decodeStoredIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_map(static fn($id): int => (int) $id, $value));
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values(array_map(static fn($id): int => (int) $id, $decoded)) : [];
        }

        return [];
    }
}
