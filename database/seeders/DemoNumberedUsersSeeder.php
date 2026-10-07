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
 * Ten full published demo candidates for QA: user+1@example.com … user+10@example.com
 * (5 male / 5 female). Password: {@see DemoNumberedUsersSeeder::DEMO_PASSWORD}.
 *
 * Idempotent: skips emails that already exist.
 *
 * Run: php artisan db:seed --class=DemoNumberedUsersSeeder
 * (or via DatabaseSeeder after master data / packages / RBAC).
 */
class DemoNumberedUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = '123456';

    public function __construct(private readonly PackagePermissionService $packagePermissionService) {}

    public function run(): void
    {
        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'CG')->value('id');
        $cityId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Raipur')->value('id');
        $guard = (string) config('auth.defaults.guard', 'web');
        $candidateRoleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', $guard)->value('id');
        $now = now();

        $maleNames = [
            ['Rohan', 'Sharma'],
            ['Vikram', 'Patel'],
            ['Aarav', 'Gupta'],
            ['Karan', 'Joshi'],
            ['Nikhil', 'Verma'],
        ];
        $femaleNames = [
            ['Ananya', 'Sharma'],
            ['Isha', 'Patel'],
            ['Meera', 'Gupta'],
            ['Pooja', 'Joshi'],
            ['Sneha', 'Verma'],
        ];

        for ($n = 1; $n <= 10; $n++) {
            $email = 'user+' . $n . '@example.com';

            if (User::withTrashed()->where('email', $email)->exists()) {
                continue;
            }

            $isMale = $n <= 5;
            $name = $isMale ? $maleNames[$n - 1] : $femaleNames[$n - 6];
            $gender = $isMale ? 'male' : 'female';
            $preferredGender = $isMale ? 'female' : 'male';
            $packageCode = match ($n % 3) {
                1 => 'TALASH_BASIC',
                2 => 'RISHTA_PRO',
                default => 'PARICHAY_FREE',
            };

            $user = User::query()->create([
                'first_name' => $name[0],
                'last_name' => $name[1],
                'email' => $email,
                'password' => self::DEMO_PASSWORD,
                'gender' => $gender,
                'phone' => '98000000' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                'date_of_birth' => sprintf('199%u-%02d-15', $n % 10, max(1, $n)),
                'current_city' => 'Raipur',
                'current_state' => 'Chhattisgarh',
                'current_country' => 'India',
                'hometown_city' => 'Raipur',
                'occupation' => $isMale ? 'Software Engineer' : 'Teacher',
                'employer' => 'Demo Org',
                'income' => 1200000.0,
                'height' => $isMale ? '5ft 10in' : '5ft 4in',
                'diet' => 'vegetarian',
                'smoking' => 'never',
                'drinking' => 'never',
                'brothers_count' => 1,
                'sisters_count' => 1,
                'family_type' => 'nuclear',
                'status' => 'active',
                'about_me' => 'Family-oriented professional seeking a respectful life partner.',
                'role_id' => $candidateRoleId > 0 ? $candidateRoleId : null,
                'marital_status' => 'single',
                'body_type' => 'average',
                'complexion' => 'fair',
                'blood_group' => 'B+',
                'manglik_status' => 'no',
                'time_of_birth' => '09:15:00',
                'zodiac_sign' => 'taurus',
                'place_of_birth_line' => 'Raipur, Chhattisgarh',
                'sub_caste' => 'General',
                'gotra' => 'Kashyapa',
                'rashi' => 'Vrishabha',
                'nakshatra' => 'Rohini',
                'father_name' => 'Father ' . $name[1],
                'father_occupation' => 'Business',
                'mother_name' => 'Mother ' . $name[1],
                'mother_occupation' => 'Homemaker',
                'completed_sections_json' => CandidateProfileSectionService::sections(),
                'profile_status' => 'published',
                'published_at' => $now,
                'is_featured' => $n <= 4,
                'featured_at' => $n <= 4 ? $now : null,
                'current_country_id' => $countryId > 0 ? $countryId : null,
                'current_state_id' => $stateId > 0 ? $stateId : null,
                'current_city_id' => $cityId > 0 ? $cityId : null,
                'birth_country_id' => $countryId > 0 ? $countryId : null,
                'birth_state_id' => $stateId > 0 ? $stateId : null,
                'birth_city_id' => $cityId > 0 ? $cityId : null,
            ]);

            if ($candidateRoleId > 0) {
                $user->assignRole('candidate');
            }

            $avatar = 10 + $n;
            $imageUrl = 'https://i.pravatar.cc/600?img=' . $avatar;
            DB::table('user_images')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
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

            DB::table('user_education_details')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'degree_id' => null,
                'field_of_study' => $isMale ? 'Computer Science' : 'Education',
                'institution_name' => 'State University',
                'education_type' => 'graduation',
                'start_year' => 2012,
                'end_year' => 2016,
                'grade_or_percentage' => '7.8 CGPA',
                'is_highest' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('user_partner_preferences')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'preferred_gender' => $preferredGender,
                'preferred_min_age' => 24,
                'preferred_max_age' => 36,
                'preferred_diet' => 'vegetarian',
                'preferred_smoking' => 'never',
                'preferred_drinking' => 'never',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $packageId = (int) DB::table('packages')->where('code', $packageCode)->value('id');

            if ($packageId > 0) {
                DB::table('subscriptions')->updateOrInsert(
                    ['user_id' => $user->id, 'package_id' => $packageId],
                    [
                        'uuid' => (string) Str::uuid(),
                        'subscription_status' => 'active',
                        'started_at' => $now,
                        'ends_at' => $now->copy()->addYear(),
                        'auto_renew' => false,
                        'renewal_source' => 'manual',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            DB::table('user_verification_documents')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'document_type' => 'aadhaar',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'document_number_masked' => 'XXXX-XXXX-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'document_front_url' => 'https://i.pravatar.cc/400?img=' . $avatar,
                    'document_back_url' => 'https://i.pravatar.cc/400?img=' . ($avatar + 1),
                    'verification_status' => 'approved',
                    'submitted_at' => $now,
                    'verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $this->packagePermissionService->syncCandidatePermissions($user->fresh());
        }
    }
}
