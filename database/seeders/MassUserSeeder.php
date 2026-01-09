<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MassUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to create 10000 users...');

        // Ensure the 'user' role exists
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $totalUsers = 100000;
        $batchSize = 1000;
        $batches = $totalUsers / $batchSize;

        // Disable query log to save memory
        DB::connection()->disableQueryLog();

        $hashedPassword = Hash::make('password');
        $faker = \Faker\Factory::create();

        $this->command->getOutput()->progressStart($batches);

        for ($batch = 0; $batch < $batches; $batch++) {
            $users = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $userNumber = $batch * $batchSize + $i + 1;

                $users[] = [
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'name' => $faker->name(),
                    'email' => 'user' . $userNumber . '@example.com',
                    'password' => $hashedPassword,
                    'email_verified_at' => $faker->boolean(80) ? now() : null,

                    // Profile Information
                    'dob' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                    'company_name' => $faker->boolean(70) ? $faker->company() : null,
                    'salary' => $faker->boolean(60) ? $faker->randomFloat(2, 30000, 200000) : null,
                    'contact_number' => $faker->boolean(80) ? $faker->phoneNumber() : null,
                    'status' => $faker->randomElement(['active', 'inactive']),

                    // Security & Login Tracking
                    'last_login_at' => $faker->boolean(70) ? $faker->dateTimeBetween('-30 days', 'now') : null,
                    'last_login_ip' => $faker->boolean(70) ? $faker->ipv4() : null,
                    'login_attempts' => $faker->numberBetween(0, 3),
                    'is_locked' => $faker->boolean(5),
                    'locked_at' => null,
                    'account_type' => $faker->randomElement(['free', 'free', 'free', 'paid', 'admin']),

                    // Two-Factor Authentication
                    'two_factor_enabled' => $faker->boolean(20),
                    'two_factor_secret' => null,
                    'password_changed_at' => $faker->boolean(60) ? $faker->dateTimeBetween('-6 months', 'now') : null,
                    'password_expires_at' => $faker->boolean(30) ? $faker->dateTimeBetween('now', '+90 days') : null,

                    // User Preferences
                    'email_notifications' => $faker->boolean(85),
                    'sms_notifications' => $faker->boolean(30),
                    'dark_mode' => $faker->boolean(40),
                    'marketing_opt_in' => $faker->boolean(60),

                    // Audit Trail
                    'created_from_ip' => $faker->ipv4(),
                    'updated_from_ip' => $faker->boolean(50) ? $faker->ipv4() : null,
                    'user_agent' => $faker->userAgent(),
                    'created_by' => null,
                    'updated_by' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert users in batch
            DB::table('users')->insert($users);

            // Get the actual IDs of the inserted users by querying with exact emails
            $emails = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $userNumber = $batch * $batchSize + $i + 1;
                $emails[] = 'user' . $userNumber . '@example.com';
            }

            $insertedUserIds = DB::table('users')->whereIn('email', $emails)->pluck('id')->toArray();

            // Assign role to all users in this batch
            $roleAssignments = [];
            foreach ($insertedUserIds as $userId) {
                $roleAssignments[] = [
                    'role_id' => $userRole->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ];
            }

            DB::table('model_has_roles')->insert($roleAssignments);

            $this->command->getOutput()->progressAdvance();

            // Clear memory every 50 batches
            if ($batch % 100 === 0) {
                gc_collect_cycles();
            }
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Successfully created 100000 users with "user" role!');
        $this->command->info('All users have password: password');
    }
}
