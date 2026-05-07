<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\CandidateProfileSectionService;
use App\Services\PackagePermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Eight demo candidate accounts (five generic + Parichay / Rishta / Talash) with full sectional completion
 * and published profiles. Password for every demo candidate: {@see DemoUsersSeeder::DEMO_PASSWORD}.
 */
class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = '1234567890';

    public function __construct(private readonly PackagePermissionService $packagePermissionService) {}

    public function run(): void
    {
        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityMumbaiId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Raipur')->value('id');
        $cityPuneId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Bilaspur')->value('id');
        $langEnId = (int) DB::table('languages')->where('code', 'en')->value('id');
        $langHiId = (int) DB::table('languages')->where('code', 'hi')->value('id');

        $districtId =
            $stateId > 0 ? (int) DB::table('districts')->where('state_id', $stateId)->orderBy('id')->value('id') : 0;
        $villageId =
            $districtId > 0
                ? (int) DB::table('villages')->where('district_id', $districtId)->orderBy('id')->value('id')
                : 0;

        $maternalFullRaipur =
            $countryId > 0 && $stateId > 0 && $cityMumbaiId > 0 && $districtId > 0 && $villageId > 0
                ? [
                    'maternal_country_id' => $countryId,
                    'maternal_state_id' => $stateId,
                    'maternal_city_id' => $cityMumbaiId,
                    'maternal_district_id' => $districtId,
                    'maternal_village_id' => $villageId,
                ]
                : [];
        $maternalStateOnly =
            $countryId > 0 && $stateId > 0
                ? [
                    'maternal_country_id' => $countryId,
                    'maternal_state_id' => $stateId,
                ]
                : [];
        $maternalFullBilaspur =
            $countryId > 0 && $stateId > 0 && $cityPuneId > 0 && $districtId > 0 && $villageId > 0
                ? [
                    'maternal_country_id' => $countryId,
                    'maternal_state_id' => $stateId,
                    'maternal_city_id' => $cityPuneId,
                    'maternal_district_id' => $districtId,
                    'maternal_village_id' => $villageId,
                ]
                : [];

        $degreeId = static fn(string $name): int => (int) DB::table('degrees')->where('name', $name)->value('id');
        /** @var list<int> */
        $surnameIds = DB::table('surnames')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn($id): int => (int) $id)
            ->all();
        $encodeIdJson = static function (?array $ids): ?string {
            if ($ids === null) {
                return null;
            }
            $filtered = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

            return $filtered === [] ? null : json_encode($filtered);
        };

        $profiles = [
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Arjun',
                        'last_name' => 'Mehta',
                        'email' => 'arjun.mehta@demo.alonti.local',
                        'gender' => 'male',
                        'phone' => '9876500101',
                        'date_of_birth' => '1995-03-15',
                        'current_city' => 'Mumbai',
                        'current_state' => 'Maharashtra',
                        'current_country' => 'India',
                        'hometown_city' => 'Pune',
                        'occupation' => 'Software Engineer',
                        'employer' => 'TechNova Pvt Ltd',
                        'income' => 1850000.0,
                        'height' => '5ft 11in',
                        'diet' => 'vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'socially',
                        'brothers_count' => 0,
                        'sisters_count' => 1,
                        'family_type' => 'nuclear',
                        'status' => 'active',
                    ],
                    $maternalFullRaipur
                ),
                'education' => [
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Computer Science',
                        'institution' => 'IIT Bombay',
                        'education_type' => 'graduation',
                        'start_year' => 2013,
                        'end_year' => 2017,
                        'grade_or_percentage' => '8.2 CGPA',
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [
                    [
                        'name' => 'Priya Mehta',
                        'gender' => 'female',
                        'relation_type' => 'sister',
                        'marital_status' => 'married',
                        'occupation' => 'Interior designer',
                        'education' => 'B.Des',
                        'age' => 32,
                        'is_elder' => true,
                        'sort_order' => 0,
                    ],
                ],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 26,
                    'preferred_max_age' => 34,
                    'preferred_min_height' => '5ft 2in',
                    'preferred_max_height' => '5ft 8in',
                    'preferred_diet' => 'vegetarian',
                    'preferred_smoking' => 'never',
                    'preferred_drinking' => 'socially',
                    'preferred_degree_ids' => array_values(
                        array_filter([$degreeId('Bachelor of Engineering')], static fn(int $id): bool => $id > 0)
                    ),
                    'preferred_location_ids' => $cityMumbaiId > 0 ? [$cityMumbaiId] : [],
                    'preferred_community_ids' => array_slice($surnameIds, 0, 2),
                    'preferred_occupation' => 'Any professional',
                    'preferred_income_min' => 800000.0,
                ],
                'avatar_img' => 12,
                'package_code' => 'PARICHAY_FREE',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Priya',
                        'last_name' => 'Shah',
                        'email' => 'priya.shah@demo.alonti.local',
                        'gender' => 'female',
                        'phone' => '9876500102',
                        'date_of_birth' => '1997-08-22',
                        'current_city' => 'Pune',
                        'current_state' => 'Maharashtra',
                        'current_country' => 'India',
                        'occupation' => 'Physician',
                        'employer' => 'City General Hospital',
                        'income' => 2200000.0,
                        'height' => '5ft 4in',
                        'diet' => 'vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'never',
                        'brothers_count' => 1,
                        'sisters_count' => 0,
                        'status' => 'active',
                    ],
                    $maternalStateOnly
                ),
                'education' => [
                    [
                        'degree' => 'MBBS',
                        'field' => 'General Medicine',
                        'institution' => 'BJ Medical College',
                        'education_type' => 'graduation',
                        'start_year' => 2015,
                        'end_year' => 2020,
                        'is_highest' => false,
                    ],
                    [
                        'degree' => 'Master of Science',
                        'field' => 'Public Health',
                        'institution' => 'TISS Mumbai',
                        'education_type' => 'post_graduation',
                        'start_year' => 2021,
                        'end_year' => 2023,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 28,
                    'preferred_max_age' => 36,
                    'preferred_diet' => 'vegetarian',
                    'preferred_smoking' => 'never',
                    'preferred_degree_ids' => array_values(
                        array_filter([$degreeId('Master of Science')], static fn(int $id): bool => $id > 0)
                    ),
                    'preferred_location_ids' => $cityPuneId > 0 ? [$cityPuneId] : [],
                    'preferred_language_id' => $langEnId,
                ],
                'avatar_img' => 45,
                'package_code' => 'TALASH_BASIC',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Rohan',
                        'last_name' => 'Kulkarni',
                        'email' => 'rohan.kulkarni@demo.alonti.local',
                        'gender' => 'male',
                        'phone' => '9876500103',
                        'date_of_birth' => '1993-11-02',
                        'current_city' => 'Mumbai',
                        'current_country' => 'India',
                        'occupation' => 'Chartered Accountant',
                        'employer' => 'Kulkarni & Associates',
                        'income' => 2400000.0,
                        'height' => '5ft 9in',
                        'diet' => 'non_vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'socially',
                        'status' => 'active',
                    ],
                    $maternalFullBilaspur
                ),
                'education' => [
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Mechanical',
                        'institution' => 'COEP Pune',
                        'education_type' => 'graduation',
                        'start_year' => 2011,
                        'end_year' => 2015,
                        'is_highest' => false,
                    ],
                    [
                        'degree' => 'MBA',
                        'field' => 'Finance',
                        'institution' => 'IIM Ahmedabad',
                        'education_type' => 'post_graduation',
                        'start_year' => 2016,
                        'end_year' => 2018,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [
                    [
                        'name' => 'Neha Kulkarni',
                        'gender' => 'female',
                        'relation_type' => 'sister',
                        'marital_status' => 'single',
                        'occupation' => 'Lawyer',
                        'age' => 29,
                        'is_elder' => false,
                        'sort_order' => 0,
                    ],
                ],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 27,
                    'preferred_max_age' => 33,
                    'preferred_community_ids' => $surnameIds !== [] ? [reset($surnameIds)] : [],
                ],
                'avatar_img' => 33,
                'package_code' => 'RISHTA_PRO',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Ananya',
                        'last_name' => 'Desai',
                        'email' => 'ananya.desai@demo.alonti.local',
                        'gender' => 'female',
                        'phone' => '9876500104',
                        'date_of_birth' => '1996-01-30',
                        'current_city' => 'Mumbai',
                        'occupation' => 'Architect',
                        'employer' => 'Studio Desai Architects',
                        'income' => 1650000.0,
                        'height' => '5ft 5in',
                        'diet' => 'vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'socially',
                        'status' => 'active',
                    ],
                    $maternalFullRaipur
                ),
                'education' => [
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Architecture',
                        'institution' => 'Sir JJ College',
                        'education_type' => 'graduation',
                        'start_year' => 2014,
                        'end_year' => 2019,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 27,
                    'preferred_max_age' => 35,
                    'preferred_location_ids' => $cityMumbaiId > 0 ? [$cityMumbaiId] : [],
                    'preferred_language_id' => $langHiId,
                ],
                'avatar_img' => 47,
                'package_code' => 'PARICHAY_FREE',
            ],
            [
                'user' => [
                    'first_name' => 'Vikram',
                    'last_name' => 'Patil',
                    'email' => 'vikram.patil@demo.alonti.local',
                    'gender' => 'male',
                    'phone' => '9876500105',
                    'date_of_birth' => '1992-07-08',
                    'current_city' => 'Pune',
                    'occupation' => 'Teacher',
                    'employer' => 'Symbiosis International School',
                    'income' => 980000.0,
                    'height' => '5ft 10in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'status' => 'active',
                ],
                'education' => [
                    [
                        'degree' => 'Master of Science',
                        'field' => 'Mathematics',
                        'institution' => 'University of Pune',
                        'education_type' => 'post_graduation',
                        'start_year' => 2014,
                        'end_year' => 2016,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 26,
                    'preferred_max_age' => 34,
                    'preferred_occupation' => 'Education or creative fields welcome',
                    'preferred_location_ids' => $cityPuneId > 0 ? [$cityPuneId] : [],
                ],
                'avatar_img' => 15,
                'package_code' => 'TALASH_BASIC',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Riya',
                        'last_name' => 'Chandrakar',
                        'email' => 'candidate.parichay@example.com',
                        'gender' => 'female',
                        'phone' => '9876500111',
                        'date_of_birth' => '1997-05-14',
                        'current_city' => 'Raipur',
                        'current_state' => 'Chhattisgarh',
                        'current_country' => 'India',
                        'occupation' => 'Graphic designer',
                        'employer' => 'Studio Nine',
                        'income' => 780000.0,
                        'height' => '5ft 4in',
                        'diet' => 'vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'never',
                        'brothers_count' => 0,
                        'sisters_count' => 1,
                        'status' => 'active',
                    ],
                    $maternalStateOnly
                ),
                'education' => [
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Design',
                        'institution' => 'NIT Raipur',
                        'education_type' => 'graduation',
                        'start_year' => 2015,
                        'end_year' => 2019,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 26,
                    'preferred_max_age' => 34,
                    'preferred_location_ids' => $cityMumbaiId > 0 ? [$cityMumbaiId] : [],
                    'preferred_language_id' => $langHiId,
                ],
                'avatar_img' => 61,
                'package_code' => 'PARICHAY_FREE',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Kunal',
                        'last_name' => 'Verma',
                        'email' => 'candidate.rishta@example.com',
                        'gender' => 'male',
                        'phone' => '9876500112',
                        'date_of_birth' => '1994-10-01',
                        'current_city' => 'Bilaspur',
                        'current_state' => 'Chhattisgarh',
                        'current_country' => 'India',
                        'occupation' => 'Civil engineer',
                        'employer' => 'CG Infra Ltd',
                        'income' => 1350000.0,
                        'height' => '5ft 10in',
                        'diet' => 'non_vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'socially',
                        'brothers_count' => 1,
                        'sisters_count' => 0,
                        'status' => 'active',
                    ],
                    $maternalFullBilaspur
                ),
                'education' => [
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Civil',
                        'institution' => 'BIT Durg',
                        'education_type' => 'graduation',
                        'start_year' => 2012,
                        'end_year' => 2016,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 25,
                    'preferred_max_age' => 32,
                    'preferred_location_ids' => $cityPuneId > 0 ? [$cityPuneId] : [],
                ],
                'avatar_img' => 62,
                'package_code' => 'RISHTA_PRO',
            ],
            [
                'user' => array_merge(
                    [
                        'first_name' => 'Sneha',
                        'last_name' => 'Bais',
                        'email' => 'candidate.talash@example.com',
                        'gender' => 'female',
                        'phone' => '9876500113',
                        'date_of_birth' => '1996-03-22',
                        'current_city' => 'Raipur',
                        'current_state' => 'Chhattisgarh',
                        'current_country' => 'India',
                        'occupation' => 'School teacher',
                        'employer' => 'DPS Raipur',
                        'income' => 620000.0,
                        'height' => '5ft 3in',
                        'diet' => 'vegetarian',
                        'smoking' => 'never',
                        'drinking' => 'never',
                        'brothers_count' => 0,
                        'sisters_count' => 0,
                        'status' => 'active',
                    ],
                    $maternalFullRaipur
                ),
                'education' => [
                    [
                        'degree' => 'Master of Science',
                        'field' => 'Education',
                        'institution' => 'HPU Shimla',
                        'education_type' => 'post_graduation',
                        'start_year' => 2018,
                        'end_year' => 2020,
                        'is_highest' => true,
                    ],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 27,
                    'preferred_max_age' => 35,
                ],
                'avatar_img' => 63,
                'package_code' => 'TALASH_BASIC',
            ],
        ];

        $now = now();
        $guard = (string) config('auth.defaults.guard', 'web');
        $candidateRoleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', $guard)->value('id');

        foreach ($profiles as $index => $row) {
            $email = $row['user']['email'];
            if (User::withTrashed()->where('email', $email)->exists()) {
                continue;
            }

            $packageCode = (string) ($row['package_code'] ?? 'PARICHAY_FREE');

            $user = User::create(
                array_merge($row['user'], [
                    'password' => self::DEMO_PASSWORD,
                    'role_id' => $candidateRoleId > 0 ? $candidateRoleId : null,
                ])
            );

            if ($candidateRoleId > 0) {
                $user->assignRole('candidate');
            }

            $userId = $user->id;

            $imageUrl = 'https://i.pravatar.cc/600?img=' . $row['avatar_img'];
            DB::table('user_images')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'image_type' => 'profile',
                'image_storage_path' => null,
                'image_url' => $imageUrl,
                'thumbnail_url' => $imageUrl,
                'icon_url' => null,
                'is_profile_photo' => true,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($row['education'] as $edu) {
                $degName = $edu['degree'];
                $degreePk = $degreeId($degName) ?: null;
                DB::table('user_education_details')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'degree_id' => $degreePk,
                    'field_of_study' => $edu['field'] ?? null,
                    'institution_name' => $edu['institution'] ?? null,
                    'education_type' => $edu['education_type'],
                    'start_year' => $edu['start_year'] ?? null,
                    'end_year' => $edu['end_year'] ?? null,
                    'grade_or_percentage' => $edu['grade_or_percentage'] ?? null,
                    'is_highest' => $edu['is_highest'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($row['siblings'] as $sib) {
                DB::table('user_siblings_details')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'name' => $sib['name'],
                    'gender' => $sib['gender'] ?? null,
                    'relation_type' => $sib['relation_type'],
                    'marital_status' => $sib['marital_status'] ?? null,
                    'occupation' => $sib['occupation'] ?? null,
                    'education' => $sib['education'] ?? null,
                    'age' => $sib['age'] ?? null,
                    'is_elder' => $sib['is_elder'] ?? false,
                    'sort_order' => $sib['sort_order'] ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $p = $row['partner'];
            DB::table('user_partner_preferences')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'preferred_gender' => $p['preferred_gender'] ?? null,
                'preferred_min_age' => $p['preferred_min_age'] ?? null,
                'preferred_max_age' => $p['preferred_max_age'] ?? null,
                'preferred_min_height' => $p['preferred_min_height'] ?? null,
                'preferred_max_height' => $p['preferred_max_height'] ?? null,
                'preferred_marital_status' => $p['preferred_marital_status'] ?? null,
                'preferred_diet' => $p['preferred_diet'] ?? null,
                'preferred_smoking' => $p['preferred_smoking'] ?? null,
                'preferred_drinking' => $p['preferred_drinking'] ?? null,
                'preferred_degree_ids' => $encodeIdJson($p['preferred_degree_ids'] ?? null),
                'preferred_location_ids' => $encodeIdJson($p['preferred_location_ids'] ?? null),
                'preferred_community_ids' => $encodeIdJson($p['preferred_community_ids'] ?? null),
                'preferred_caste' => $p['preferred_caste'] ?? null,
                'preferred_occupation' => $p['preferred_occupation'] ?? null,
                'preferred_income_min' => $p['preferred_income_min'] ?? null,
                'preferred_language_id' => $p['preferred_language_id'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->attachSubscription($userId, $packageCode);

            $published = $user->fresh();
            if ($published instanceof User) {
                $this->finalizePublishedCandidate(
                    $published,
                    $index,
                    $countryId,
                    $stateId,
                    $cityMumbaiId,
                    $districtId,
                    $villageId
                );
                $this->packagePermissionService->syncCandidatePermissions($published->fresh());
            }

            // Second gallery image for variety (first five users)
            if ($index < 5) {
                DB::table('user_images')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'image_type' => 'gallery',
                    'image_storage_path' => null,
                    'image_url' => 'https://picsum.photos/id/' . (100 + $index) . '/800/600',
                    'thumbnail_url' => 'https://picsum.photos/id/' . (100 + $index) . '/400/300',
                    'icon_url' => null,
                    'is_profile_photo' => false,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->seedParichayDemoMatches();
    }

    /**
     * Demo matches for candidate.parichay@example.com → rishta & talash demo accounts.
     * Runs after profile loop so it still applies when those users already existed (skipped in loop).
     */
    private function seedParichayDemoMatches(): void
    {
        $parichayId = (int) User::query()->where('email', 'candidate.parichay@example.com')->value('id');
        $rishtaId = (int) User::query()->where('email', 'candidate.rishta@example.com')->value('id');
        $talashId = (int) User::query()->where('email', 'candidate.talash@example.com')->value('id');

        if ($parichayId === 0 || $rishtaId === 0 || $talashId === 0) {
            return;
        }

        $now = now();
        $pairs = [
            [
                'matched_user_id' => $rishtaId,
                'match_score' => 88,
                'match_reason_json' => json_encode(
                    [
                        'summary' => 'Demo match: complementary preferences (Rishta package profile).',
                    ],
                    JSON_THROW_ON_ERROR
                ),
            ],
            [
                'matched_user_id' => $talashId,
                'match_score' => 84,
                'match_reason_json' => json_encode(
                    [
                        'summary' => 'Demo match: shared region and lifestyle signals (Talash package profile).',
                    ],
                    JSON_THROW_ON_ERROR
                ),
            ],
        ];

        foreach ($pairs as $row) {
            $existing = DB::table('matches')
                ->where('user_id', $parichayId)
                ->where('matched_user_id', $row['matched_user_id'])
                ->first();

            if ($existing !== null) {
                DB::table('matches')
                    ->where('id', $existing->id)
                    ->update([
                        'match_score' => $row['match_score'],
                        'match_reason_json' => $row['match_reason_json'],
                        'match_status' => 'active',
                        'generated_by' => 'system',
                        'generated_at' => $now,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('matches')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $parichayId,
                'matched_user_id' => $row['matched_user_id'],
                'match_score' => $row['match_score'],
                'match_reason_json' => $row['match_reason_json'],
                'match_status' => 'active',
                'generated_by' => 'system',
                'generated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function attachSubscription(int $userId, string $packageCode): void
    {
        $now = now();
        $packageId = (int) DB::table('packages')->where('code', $packageCode)->value('id');
        if ($packageId === 0) {
            return;
        }

        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $packageId],
            [
                'uuid' => (string) Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addDays(365),
                'auto_renew' => true,
                'renewal_source' => 'manual',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    private function finalizePublishedCandidate(
        User $user,
        int $index,
        int $countryId,
        int $stateId,
        int $birthCityId,
        int $districtId,
        int $villageId
    ): void {
        $now = now();
        $birthColumns = [];
        if ($countryId > 0) {
            $birthColumns['birth_country_id'] = $countryId;
        }
        if ($stateId > 0) {
            $birthColumns['birth_state_id'] = $stateId;
        }
        if ($birthCityId > 0) {
            $birthColumns['birth_city_id'] = $birthCityId;
        }
        if ($districtId > 0) {
            $birthColumns['birth_district_id'] = $districtId;
        }
        if ($villageId > 0) {
            $birthColumns['birth_village_id'] = $villageId;
        }

        $user
            ->forceFill(
                array_merge(
                    [
                        'marital_status' => 'single',
                        'body_type' => 'average',
                        'complexion' => 'fair',
                        'blood_group' => 'O+',
                        'manglik_status' => 'no',
                        'about_me' => 'Demo profile seeded for development and QA.',
                        'time_of_birth' => '10:30:00',
                        'zodiac_sign' => 'aries',
                        'place_of_birth_line' => 'Raipur, Chhattisgarh',
                        'father_name' => 'Father ' . $user->last_name,
                        'father_occupation' => 'Business',
                        'mother_name' => 'Mother ' . $user->last_name,
                        'mother_occupation' => 'Homemaker',
                        'completed_sections_json' => CandidateProfileSectionService::sections(),
                        'profile_status' => 'published',
                        'published_at' => $now,
                        'is_featured' => $index < 5,
                        'featured_at' => $index < 5 ? $now : null,
                    ],
                    $birthColumns
                )
            )
            ->save();
    }
}
