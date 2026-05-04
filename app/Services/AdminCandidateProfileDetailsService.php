<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds admin "profile details" payload: same shape as {@see \App\Http\Resources\Api\V1\CandidateUserResource}
 * with FK-based geography and master lists resolved to the same string|null per level as `locationFamilyRoots.current`.
 */
class AdminCandidateProfileDetailsService
{
    /**
     * @return array<string, mixed>
     */
    public function buildForCandidate(User $user): array
    {
        $user->loadMissing('primaryRole');

        $photos = $this->loadPhotos($user->id);
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

        $educationRows = DB::table('user_education_details')
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
            ]);

        $siblings = $this->loadSiblings($user->id);
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

        $maps = $this->loadMasterMaps(
            $user,
            $partner,
            $educationRows,
            $partnerDegreeIds,
            $partnerLocationIds,
            $partnerCommunityIds
        );

        $qualifications = $educationRows
            ->map(function (object $row) use ($maps): array {
                $degId = data_get($row, 'degree_id');

                return [
                    'id' => (int) data_get($row, 'id'),
                    'degreeName' => $degId !== null && (int) $degId > 0
                            ? data_get($maps['degrees']->get((int) $degId), 'name')
                            : null,
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

        $birthPlace = $this->resolveGeoChain(
            $user,
            $maps,
            [
                'country' => 'birth_country_id',
                'state' => 'birth_state_id',
                'city' => 'birth_city_id',
                'district' => 'birth_district_id',
                'village' => 'birth_village_id',
            ],
            [
                'country' => 'place_of_birth_country',
                'state' => 'place_of_birth_state',
                'city' => 'place_of_birth_city',
                'district' => 'place_of_birth_district',
                'village' => 'place_of_birth_village',
            ]
        );

        $maternalPlace = $this->resolveGeoChain(
            $user,
            $maps,
            [
                'country' => 'maternal_country_id',
                'state' => 'maternal_state_id',
                'city' => 'maternal_city_id',
                'district' => 'maternal_district_id',
                'village' => 'maternal_village_id',
            ],
            [
                'country' => null,
                'state' => null,
                'city' => null,
                'district' => null,
                'village' => null,
            ]
        );

        $preferredLocationResolved = $this->resolvePartnerPreferredLocationStrings($partner, $maps);

        $preferredDegreeNames = $this->orderedNames($partnerDegreeIds, $maps['degrees'], 'name');
        $preferredCityNames = $this->orderedNames($partnerLocationIds, $maps['cities'], 'name');
        $preferredCommunityNames = $this->orderedNames($partnerCommunityIds, $maps['surnames'], 'name');

        $preferredLanguageName = null;
        $langId = $partner !== null ? data_get($partner, 'preferred_language_id') : null;
        if ($langId !== null && (int) $langId > 0) {
            $preferredLanguageName = data_get($maps['languages']->get((int) $langId), 'name');
        }

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
                    'birthPlace' => $birthPlace,
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
                    'maternal' => $maternalPlace,
                ],
                'careerEducation' => [
                    'occupation' => data_get($user, 'occupation'),
                    'employer' => data_get($user, 'employer'),
                    'income' => data_get($user, 'income'),
                    'maritalStatus' => data_get($user, 'marital_status'),
                    'qualifications' => $qualifications,
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
                    'preferredLanguageName' => $preferredLanguageName,
                    'preferredDegrees' => $preferredDegreeNames,
                    'preferredCities' => $preferredCityNames,
                    'preferredCommunities' => $preferredCommunityNames,
                    'preferredOccupation' => data_get($partner, 'preferred_occupation'),
                    'preferredLocation' => $preferredLocationResolved,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadPhotos(int $userId): array
    {
        return DB::table('user_images')
            ->where('user_id', $userId)
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
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadSiblings(int $userId): array
    {
        return DB::table('user_siblings_details')
            ->where('user_id', $userId)
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
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $educationRows
     * @param  list<int>  $partnerDegreeIds
     * @param  list<int>  $partnerLocationIds
     * @param  list<int>  $partnerCommunityIds
     * @return array{
     *     countries: Collection<(int|string), \stdClass>,
     *     states: Collection<(int|string), \stdClass>,
     *     cities: Collection<(int|string), \stdClass>,
     *     districts: Collection<(int|string), \stdClass>,
     *     villages: Collection<(int|string), \stdClass>,
     *     degrees: Collection<(int|string), \stdClass>,
     *     languages: Collection<(int|string), \stdClass>,
     *     surnames: Collection<(int|string), \stdClass>
     * }
     */
    private function loadMasterMaps(
        User $user,
        ?object $partner,
        Collection $educationRows,
        array $partnerDegreeIds,
        array $partnerLocationIds,
        array $partnerCommunityIds
    ): array {
        $countryIds = [];
        $stateIds = [];
        $cityIds = [];
        $districtIds = [];
        $villageIds = [];
        $degreeIds = [];
        $languageIds = [];
        $surnameIds = [];

        $push = static function (array &$bucket, mixed $v): void {
            if ($v !== null && (int) $v > 0) {
                $bucket[(int) $v] = true;
            }
        };

        $push($countryIds, $user->birth_country_id);
        $push($stateIds, $user->birth_state_id);
        $push($cityIds, $user->birth_city_id);
        $push($districtIds, $user->birth_district_id);
        $push($villageIds, $user->birth_village_id);

        $push($countryIds, $user->maternal_country_id);
        $push($stateIds, $user->maternal_state_id);
        $push($cityIds, $user->maternal_city_id);
        $push($districtIds, $user->maternal_district_id);
        $push($villageIds, $user->maternal_village_id);

        if ($partner !== null) {
            $push($countryIds, data_get($partner, 'preferred_country_id'));
            $push($stateIds, data_get($partner, 'preferred_state_id'));
            $push($cityIds, data_get($partner, 'preferred_city_id'));
            $push($languageIds, data_get($partner, 'preferred_language_id'));
        }

        foreach ($educationRows as $row) {
            $push($degreeIds, data_get($row, 'degree_id'));
        }
        foreach ($partnerDegreeIds as $id) {
            $push($degreeIds, $id);
        }
        foreach ($partnerLocationIds as $id) {
            $push($cityIds, $id);
        }
        foreach ($partnerCommunityIds as $id) {
            $push($surnameIds, $id);
        }

        $countryIds = array_keys($countryIds);
        $stateIds = array_keys($stateIds);
        $cityIds = array_keys($cityIds);
        $districtIds = array_keys($districtIds);
        $villageIds = array_keys($villageIds);
        $degreeIds = array_keys($degreeIds);
        $languageIds = array_keys($languageIds);
        $surnameIds = array_keys($surnameIds);

        return [
            'countries' => $this->fetchMap('countries', $countryIds, ['id', 'name', 'iso2']),
            'states' => $this->fetchMap('states', $stateIds, ['id', 'name', 'code']),
            'cities' => $this->fetchMap('cities', $cityIds, ['id', 'name']),
            'districts' => $this->fetchMap('districts', $districtIds, ['id', 'name']),
            'villages' => $this->fetchMap('villages', $villageIds, ['id', 'name']),
            'degrees' => $this->fetchMap('degrees', $degreeIds, ['id', 'name']),
            'languages' => $this->fetchMap('languages', $languageIds, ['id', 'name']),
            'surnames' => $this->fetchMap('surnames', $surnameIds, ['id', 'name']),
        ];
    }

    /**
     * @param  list<int>  $ids
     * @param  list<string>  $columns
     * @return Collection<(int|string), \stdClass>
     */
    private function fetchMap(string $table, array $ids, array $columns): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return DB::table($table)->whereIn('id', $ids)->get($columns)->keyBy('id');
    }

    /**
     * Same shape as `locationFamilyRoots.current`: string or null per level (no nested name/code objects).
     *
     * @param  array<string, string>  $fkKeys  logical key => user column
     * @param  array<string, string|null>  $legacyKeys  logical key => user legacy text column or null
     * @return array{country: ?string, state: ?string, city: ?string, district: ?string, village: ?string}
     */
    private function resolveGeoChain(User $user, array $maps, array $fkKeys, array $legacyKeys): array
    {
        $out = [];
        foreach (['country', 'state', 'city', 'district', 'village'] as $level) {
            $fkCol = $fkKeys[$level];
            $id = data_get($user, $fkCol);
            $legacyCol = $legacyKeys[$level] ?? null;
            $legacy = $legacyCol !== null ? data_get($user, $legacyCol) : null;

            $out[$level] = $this->resolveOneGeoLevelName($id, $level, $maps, $legacy);
        }

        return $out;
    }

    /**
     * @return array{country: ?string, state: ?string, city: ?string, district: ?string, village: ?string}
     */
    private function resolvePartnerPreferredLocationStrings(?object $partner, array $maps): array
    {
        $empty = [
            'country' => null,
            'state' => null,
            'city' => null,
            'district' => null,
            'village' => null,
        ];
        if ($partner === null) {
            return $empty;
        }

        return [
            'country' => $this->resolveOneGeoLevelName(
                data_get($partner, 'preferred_country_id'),
                'country',
                $maps,
                null
            ),
            'state' => $this->resolveOneGeoLevelName(data_get($partner, 'preferred_state_id'), 'state', $maps, null),
            'city' => $this->resolveOneGeoLevelName(data_get($partner, 'preferred_city_id'), 'city', $maps, null),
            'district' => null,
            'village' => null,
        ];
    }

    private function resolveOneGeoLevelName(mixed $id, string $level, array $maps, mixed $legacyText): ?string
    {
        $tableKey = match ($level) {
            'country' => 'countries',
            'state' => 'states',
            'city' => 'cities',
            'district' => 'districts',
            'village' => 'villages',
            default => 'countries',
        };
        /** @var Collection<int, object> $collection */
        $collection = $maps[$tableKey];
        if ($id !== null && (int) $id > 0) {
            $row = $collection->get((int) $id);
            if ($row !== null) {
                $name = data_get($row, 'name');

                return is_string($name) ? $name : (is_scalar($name) ? (string) $name : null);
            }
        }
        if ($legacyText !== null && is_string($legacyText) && trim($legacyText) !== '') {
            return $legacyText;
        }

        return null;
    }

    /**
     * @param  list<int>  $ids
     * @return list<?string>
     */
    private function orderedNames(array $ids, Collection $map, string $nameKey): array
    {
        $names = [];
        foreach ($ids as $id) {
            $row = $map->get((int) $id);
            $names[] = $row !== null ? data_get($row, $nameKey) : null;
        }

        return $names;
    }

    /**
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
