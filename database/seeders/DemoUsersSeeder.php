<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ten demo accounts with profile images, education, siblings (where noted), and partner preferences.
 * Password for every demo user: Password@demo1
 */
class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Password@demo1';

    public function run(): void
    {
        $countryId = (int) DB::table('countries')->where('iso2', 'IN')->value('id');
        $stateId = (int) DB::table('states')->where('country_id', $countryId)->where('code', 'MH')->value('id');
        $cityMumbaiId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Mumbai')->value('id');
        $cityPuneId = (int) DB::table('cities')->where('state_id', $stateId)->where('name', 'Pune')->value('id');
        $langEnId = (int) DB::table('languages')->where('code', 'en')->value('id');
        $langHiId = (int) DB::table('languages')->where('code', 'hi')->value('id');

        $degreeId = static fn (string $name): int => (int) DB::table('degrees')->where('name', $name)->value('id');

        $profiles = [
            [
                'user' => [
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
                    'income' => 1850000.00,
                    'height' => '5ft 11in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'hobbies' => 'Cricket, trekking, classical music',
                    'brothers_count' => 0,
                    'sisters_count' => 1,
                    'family_type' => 'nuclear',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Bachelor of Engineering', 'field' => 'Computer Science', 'institution' => 'IIT Bombay', 'education_type' => 'graduation', 'start_year' => 2013, 'end_year' => 2017, 'grade_or_percentage' => '8.2 CGPA', 'is_highest' => true],
                ],
                'siblings' => [
                    ['name' => 'Priya Mehta', 'gender' => 'female', 'relation_type' => 'sister', 'marital_status' => 'married', 'occupation' => 'Interior designer', 'education' => 'B.Des', 'age' => 32, 'is_elder' => true, 'sort_order' => 0],
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
                    'preferred_education' => 'Graduate or higher',
                    'preferred_occupation' => 'Any professional',
                    'preferred_income_min' => 800000.00,
                    'preferred_city_id' => $cityMumbaiId,
                    'preferred_community' => 'Open to all',
                    'preferred_other_criteria' => 'Family-oriented, enjoys travel.',
                ],
                'avatar_img' => 12,
            ],
            [
                'user' => [
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
                    'income' => 2200000.00,
                    'height' => '5ft 4in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'hobbies' => 'Reading, yoga, volunteering',
                    'brothers_count' => 1,
                    'sisters_count' => 0,
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'MBBS', 'field' => 'General Medicine', 'institution' => 'BJ Medical College', 'education_type' => 'graduation', 'start_year' => 2015, 'end_year' => 2020, 'is_highest' => false],
                    ['degree' => 'Master of Science', 'field' => 'Public Health', 'institution' => 'TISS Mumbai', 'education_type' => 'post_graduation', 'start_year' => 2021, 'end_year' => 2023, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 28,
                    'preferred_max_age' => 36,
                    'preferred_diet' => 'vegetarian',
                    'preferred_smoking' => 'never',
                    'preferred_education' => 'Graduate or higher',
                    'preferred_city_id' => $cityPuneId,
                    'preferred_language_id' => $langEnId,
                    'preferred_other_criteria' => 'Supportive of career; values health and fitness.',
                ],
                'avatar_img' => 45,
            ],
            [
                'user' => [
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
                    'income' => 2400000.00,
                    'height' => '5ft 9in',
                    'diet' => 'non_vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'interests' => 'Stock markets, marathon running',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Bachelor of Engineering', 'field' => 'Mechanical', 'institution' => 'COEP Pune', 'education_type' => 'graduation', 'start_year' => 2011, 'end_year' => 2015, 'is_highest' => false],
                    ['degree' => 'MBA', 'field' => 'Finance', 'institution' => 'IIM Ahmedabad', 'education_type' => 'post_graduation', 'start_year' => 2016, 'end_year' => 2018, 'is_highest' => true],
                ],
                'siblings' => [
                    ['name' => 'Neha Kulkarni', 'gender' => 'female', 'relation_type' => 'sister', 'marital_status' => 'single', 'occupation' => 'Lawyer', 'age' => 29, 'is_elder' => false, 'sort_order' => 0],
                ],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 27,
                    'preferred_max_age' => 33,
                    'preferred_country_id' => $countryId,
                    'preferred_state_id' => $stateId,
                    'preferred_community' => 'Prefer similar values around family and finance.',
                ],
                'avatar_img' => 33,
            ],
            [
                'user' => [
                    'first_name' => 'Ananya',
                    'last_name' => 'Desai',
                    'email' => 'ananya.desai@demo.alonti.local',
                    'gender' => 'female',
                    'phone' => '9876500104',
                    'date_of_birth' => '1996-01-30',
                    'current_city' => 'Mumbai',
                    'occupation' => 'Architect',
                    'employer' => 'Studio Desai Architects',
                    'income' => 1650000.00,
                    'height' => '5ft 5in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'hobbies' => 'Sketching, heritage walks',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Bachelor of Engineering', 'field' => 'Architecture', 'institution' => 'Sir JJ College', 'education_type' => 'graduation', 'start_year' => 2014, 'end_year' => 2019, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 27,
                    'preferred_max_age' => 35,
                    'preferred_city_id' => $cityMumbaiId,
                    'preferred_language_id' => $langHiId,
                ],
                'avatar_img' => 47,
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
                    'income' => 980000.00,
                    'height' => '5ft 10in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Master of Science', 'field' => 'Mathematics', 'institution' => 'University of Pune', 'education_type' => 'post_graduation', 'start_year' => 2014, 'end_year' => 2016, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 26,
                    'preferred_max_age' => 34,
                    'preferred_occupation' => 'Education or creative fields welcome',
                    'preferred_city_id' => $cityPuneId,
                ],
                'avatar_img' => 15,
            ],
            [
                'user' => [
                    'first_name' => 'Sneha',
                    'last_name' => 'Joshi',
                    'email' => 'sneha.joshi@demo.alonti.local',
                    'gender' => 'female',
                    'phone' => '9876500106',
                    'date_of_birth' => '1998-04-12',
                    'current_city' => 'Mumbai',
                    'occupation' => 'Software Engineer',
                    'employer' => 'CloudScale India',
                    'income' => 1420000.00,
                    'height' => '5ft 3in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'status' => 'pending_verification',
                ],
                'education' => [
                    ['degree' => 'Bachelor of Engineering', 'field' => 'Information Technology', 'institution' => 'VJTI Mumbai', 'education_type' => 'graduation', 'start_year' => 2016, 'end_year' => 2020, 'is_highest' => true],
                ],
                'siblings' => [
                    ['name' => 'Rahul Joshi', 'gender' => 'male', 'relation_type' => 'brother', 'marital_status' => 'single', 'occupation' => 'Student', 'age' => 22, 'is_elder' => false, 'sort_order' => 0],
                ],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 25,
                    'preferred_max_age' => 32,
                    'preferred_city_id' => $cityMumbaiId,
                ],
                'avatar_img' => 44,
            ],
            [
                'user' => [
                    'first_name' => 'Karan',
                    'last_name' => 'Rao',
                    'email' => 'karan.rao@demo.alonti.local',
                    'gender' => 'male',
                    'phone' => '9876500107',
                    'date_of_birth' => '1991-12-20',
                    'current_city' => 'Mumbai',
                    'occupation' => 'Physician',
                    'employer' => 'Lilavati Hospital',
                    'income' => 3100000.00,
                    'height' => '6ft 0in',
                    'diet' => 'non_vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'MBBS', 'field' => 'Medicine', 'institution' => 'KEM Hospital', 'education_type' => 'graduation', 'start_year' => 2009, 'end_year' => 2015, 'is_highest' => false],
                    ['degree' => 'Master of Science', 'field' => 'Cardiology fellowship', 'institution' => 'AIIMS Delhi', 'education_type' => 'post_graduation', 'start_year' => 2018, 'end_year' => 2021, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 28,
                    'preferred_max_age' => 36,
                    'preferred_education' => 'Graduate or higher',
                    'preferred_country_id' => $countryId,
                ],
                'avatar_img' => 52,
            ],
            [
                'user' => [
                    'first_name' => 'Meera',
                    'last_name' => 'Iyer',
                    'email' => 'meera.iyer@demo.alonti.local',
                    'gender' => 'female',
                    'phone' => '9876500108',
                    'date_of_birth' => '1994-09-05',
                    'current_city' => 'Pune',
                    'occupation' => 'Chartered Accountant',
                    'employer' => 'Deloitte India',
                    'income' => 1950000.00,
                    'height' => '5ft 6in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'MBA', 'field' => 'Finance', 'institution' => 'NMIMS Mumbai', 'education_type' => 'post_graduation', 'start_year' => 2016, 'end_year' => 2018, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 28,
                    'preferred_max_age' => 36,
                    'preferred_income_min' => 1200000.00,
                    'preferred_state_id' => $stateId,
                ],
                'avatar_img' => 48,
            ],
            [
                'user' => [
                    'first_name' => 'Aditya',
                    'last_name' => 'Nair',
                    'email' => 'aditya.nair@demo.alonti.local',
                    'gender' => 'male',
                    'phone' => '9876500109',
                    'date_of_birth' => '1996-06-18',
                    'current_city' => 'Mumbai',
                    'occupation' => 'Software Engineer',
                    'employer' => 'FinTech Labs',
                    'income' => 2100000.00,
                    'height' => '5ft 8in',
                    'diet' => 'non_vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'socially',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Bachelor of Engineering', 'field' => 'Electronics', 'institution' => 'BITS Pilani', 'education_type' => 'graduation', 'start_year' => 2014, 'end_year' => 2018, 'is_highest' => true],
                ],
                'siblings' => [],
                'partner' => [
                    'preferred_gender' => 'female',
                    'preferred_min_age' => 24,
                    'preferred_max_age' => 31,
                    'preferred_city_id' => $cityMumbaiId,
                    'preferred_language_id' => $langEnId,
                ],
                'avatar_img' => 27,
            ],
            [
                'user' => [
                    'first_name' => 'Kavita',
                    'last_name' => 'Reddy',
                    'email' => 'kavita.reddy@demo.alonti.local',
                    'gender' => 'female',
                    'phone' => '9876500110',
                    'date_of_birth' => '1995-02-28',
                    'current_city' => 'Pune',
                    'occupation' => 'Teacher',
                    'employer' => 'KV Pune',
                    'income' => 920000.00,
                    'height' => '5ft 2in',
                    'diet' => 'vegetarian',
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'status' => 'active',
                ],
                'education' => [
                    ['degree' => 'Master of Science', 'field' => 'Physics', 'institution' => 'Fergusson College', 'education_type' => 'post_graduation', 'start_year' => 2016, 'end_year' => 2018, 'is_highest' => true],
                ],
                'siblings' => [
                    ['name' => 'Suresh Reddy', 'gender' => 'male', 'relation_type' => 'brother', 'marital_status' => 'married', 'occupation' => 'Bank officer', 'age' => 35, 'is_elder' => true, 'sort_order' => 0],
                ],
                'partner' => [
                    'preferred_gender' => 'male',
                    'preferred_min_age' => 28,
                    'preferred_max_age' => 38,
                    'preferred_city_id' => $cityPuneId,
                    'preferred_diet' => 'vegetarian',
                ],
                'avatar_img' => 42,
            ],
        ];

        $now = now();

        foreach ($profiles as $index => $row) {
            $email = $row['user']['email'];
            if (User::withTrashed()->where('email', $email)->exists()) {
                continue;
            }

            $user = User::create(array_merge($row['user'], [
                'password' => self::DEMO_PASSWORD,
            ]));

            $userId = $user->id;

            $imageUrl = 'https://i.pravatar.cc/600?img=' . $row['avatar_img'];
            DB::table('user_images')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'image_type' => 'profile',
                'image_url' => $imageUrl,
                'thumbnail_url' => $imageUrl,
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
                'preferred_education' => $p['preferred_education'] ?? null,
                'preferred_occupation' => $p['preferred_occupation'] ?? null,
                'preferred_income_min' => $p['preferred_income_min'] ?? null,
                'preferred_country_id' => $p['preferred_country_id'] ?? null,
                'preferred_state_id' => $p['preferred_state_id'] ?? null,
                'preferred_city_id' => $p['preferred_city_id'] ?? null,
                'preferred_community' => $p['preferred_community'] ?? null,
                'preferred_language_id' => $p['preferred_language_id'] ?? null,
                'preferred_other_criteria' => $p['preferred_other_criteria'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Second gallery image for variety (first five users)
            if ($index < 5) {
                DB::table('user_images')->insert([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'image_type' => 'gallery',
                    'image_url' => 'https://picsum.photos/id/' . (100 + $index) . '/800/600',
                    'thumbnail_url' => 'https://picsum.photos/id/' . (100 + $index) . '/400/300',
                    'is_profile_photo' => false,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
