<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Minimal geography and reference rows so demo users can reference FKs
 * (partner preferences, education degrees, etc.).
 */
class DemoMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (DB::table('countries')->where('iso2', 'IN')->doesntExist()) {
            DB::table('countries')->insert([
                'name' => 'India',
                'iso2' => 'IN',
                'iso3' => 'IND',
                'phone_code' => '+91',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $languages = [['name' => 'English', 'code' => 'en'], ['name' => 'Hindi', 'code' => 'hi']];
        foreach ($languages as $lang) {
            if (DB::table('languages')->where('code', $lang['code'])->doesntExist()) {
                DB::table('languages')->insert([
                    'name' => $lang['name'],
                    'code' => $lang['code'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $degrees = [
            ['name' => 'Bachelor of Engineering', 'degree_type' => 'undergraduate', 'sort_order' => 10],
            ['name' => 'Master of Science', 'degree_type' => 'postgraduate', 'sort_order' => 20],
            ['name' => 'MBA', 'degree_type' => 'postgraduate', 'sort_order' => 30],
            ['name' => 'MBBS', 'degree_type' => 'undergraduate', 'sort_order' => 40],
        ];
        foreach ($degrees as $deg) {
            if (DB::table('degrees')->where('name', $deg['name'])->doesntExist()) {
                DB::table('degrees')->insert([
                    'name' => $deg['name'],
                    'degree_type' => $deg['degree_type'],
                    'sort_order' => $deg['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $occupations = [
            ['name' => 'Software Engineer', 'category' => 'Technology', 'sort_order' => 10],
            ['name' => 'Physician', 'category' => 'Healthcare', 'sort_order' => 20],
            ['name' => 'Chartered Accountant', 'category' => 'Finance', 'sort_order' => 30],
            ['name' => 'Architect', 'category' => 'Design', 'sort_order' => 40],
            ['name' => 'Teacher', 'category' => 'Education', 'sort_order' => 50],
        ];
        foreach ($occupations as $occ) {
            if (DB::table('occupations')->where('name', $occ['name'])->doesntExist()) {
                DB::table('occupations')->insert([
                    'name' => $occ['name'],
                    'category' => $occ['category'],
                    'sort_order' => $occ['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $surnames = ['Chandrakar', 'Verma', 'Bais', 'Kashyap', 'Chandrawanshi', 'Kaushik', 'Deshmukh'];
        foreach ($surnames as $surname) {
            if (DB::table('surnames')->where('name', $surname)->doesntExist()) {
                DB::table('surnames')->insert([
                    'name' => $surname,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $incomeRanges = [
            ['name' => 'Below 5 LPA', 'min_amount' => 0, 'max_amount' => 500000, 'sort_order' => 1],
            ['name' => '5–10 LPA', 'min_amount' => 500000, 'max_amount' => 1000000, 'sort_order' => 2],
            ['name' => '10–20 LPA', 'min_amount' => 1000000, 'max_amount' => 2000000, 'sort_order' => 3],
            ['name' => '20 LPA+', 'min_amount' => 2000000, 'max_amount' => null, 'sort_order' => 4],
        ];
        foreach ($incomeRanges as $range) {
            if (DB::table('income_ranges')->where('name', $range['name'])->doesntExist()) {
                DB::table('income_ranges')->insert([
                    'name' => $range['name'],
                    'min_amount' => $range['min_amount'],
                    'max_amount' => $range['max_amount'],
                    'currency' => 'INR',
                    'sort_order' => $range['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
