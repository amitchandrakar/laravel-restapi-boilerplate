<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDegreesOccupationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $degrees = [
            ['name' => '10th Pass', 'degree_type' => 'secondary'],
            ['name' => '12th Pass', 'degree_type' => 'higher_secondary'],
            ['name' => 'Diploma', 'degree_type' => 'diploma'],
            ['name' => 'ITI', 'degree_type' => 'vocational'],
            ['name' => 'BA', 'degree_type' => 'undergraduate'],
            ['name' => 'BCom', 'degree_type' => 'undergraduate'],
            ['name' => 'BSc', 'degree_type' => 'undergraduate'],
            ['name' => 'BCA', 'degree_type' => 'undergraduate'],
            ['name' => 'BE/BTech', 'degree_type' => 'undergraduate'],
            ['name' => 'MBBS', 'degree_type' => 'undergraduate'],
            ['name' => 'LLB', 'degree_type' => 'undergraduate'],
            ['name' => 'BEd', 'degree_type' => 'undergraduate'],
            ['name' => 'MA', 'degree_type' => 'postgraduate'],
            ['name' => 'MCom', 'degree_type' => 'postgraduate'],
            ['name' => 'MSc', 'degree_type' => 'postgraduate'],
            ['name' => 'MCA', 'degree_type' => 'postgraduate'],
            ['name' => 'ME/MTech', 'degree_type' => 'postgraduate'],
            ['name' => 'MBA/PGDM', 'degree_type' => 'postgraduate'],
            ['name' => 'MD/MS', 'degree_type' => 'postgraduate'],
            ['name' => 'LLM', 'degree_type' => 'postgraduate'],
            ['name' => 'PhD', 'degree_type' => 'doctorate'],
            ['name' => 'CA', 'degree_type' => 'professional'],
            ['name' => 'CS', 'degree_type' => 'professional'],
            ['name' => 'CMA', 'degree_type' => 'professional'],
        ];

        foreach ($degrees as $index => $degree) {
            DB::table('degrees')->updateOrInsert(
                ['name' => $degree['name']],
                [
                    'degree_type' => $degree['degree_type'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $occupations = [
            ['name' => 'Software Engineer', 'category' => 'Technology'],
            ['name' => 'Teacher', 'category' => 'Education'],
            ['name' => 'Doctor', 'category' => 'Healthcare'],
            ['name' => 'Nurse', 'category' => 'Healthcare'],
            ['name' => 'Pharmacist', 'category' => 'Healthcare'],
            ['name' => 'Chartered Accountant', 'category' => 'Finance'],
            ['name' => 'Banker', 'category' => 'Finance'],
            ['name' => 'Lawyer', 'category' => 'Legal'],
            ['name' => 'Civil Engineer', 'category' => 'Engineering'],
            ['name' => 'Mechanical Engineer', 'category' => 'Engineering'],
            ['name' => 'Electrical Engineer', 'category' => 'Engineering'],
            ['name' => 'Architect', 'category' => 'Design'],
            ['name' => 'Government Employee', 'category' => 'Government'],
            ['name' => 'Police/Defence', 'category' => 'Government'],
            ['name' => 'Business Owner', 'category' => 'Business'],
            ['name' => 'Entrepreneur', 'category' => 'Business'],
            ['name' => 'Farmer', 'category' => 'Agriculture'],
            ['name' => 'Self Employed', 'category' => 'Business'],
            ['name' => 'Private Sector Employee', 'category' => 'Corporate'],
            ['name' => 'Public Sector Employee', 'category' => 'Government'],
            ['name' => 'Journalist', 'category' => 'Media'],
            ['name' => 'Artist', 'category' => 'Creative'],
            ['name' => 'Student', 'category' => 'Education'],
            ['name' => 'Homemaker', 'category' => 'Other'],
        ];

        foreach ($occupations as $index => $occupation) {
            DB::table('occupations')->updateOrInsert(
                ['name' => $occupation['name']],
                [
                    'category' => $occupation['category'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
