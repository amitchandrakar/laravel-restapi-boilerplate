<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTeamUsersSeeder extends Seeder
{
    private const PASSWORD = '123456';

    public function run(): void
    {
        $guard = (string) config('auth.defaults.guard', RbacSeeder::GUARD);
        $role = Role::query()->where('name', 'reviewer')->where('guard_name', $guard)->first();
        if (!$role instanceof Role) {
            return;
        }

        $departments = ['Operations', 'Support', 'Moderation', 'Compliance', 'Onboarding'];
        $jobTitles = ['Executive', 'Senior Executive', 'Lead', 'Associate', 'Coordinator'];
        $cities = ['Raipur', 'Bilaspur', 'Durg', 'Rajnandgaon', 'Korba'];

        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf('team%02d@example.com', $i);
            $user = User::withTrashed()->where('email', $email)->first();

            if (!$user instanceof User) {
                $user = User::query()->create([
                    'first_name' => 'Team',
                    'last_name' => sprintf('Member%02d', $i),
                    'email' => $email,
                    'phone' => sprintf('900000%04d', $i),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'department' => $departments[$i % count($departments)],
                    'job_title' => $jobTitles[$i % count($jobTitles)],
                    'current_city' => $cities[$i % count($cities)],
                    'status' => 'active',
                    'role_id' => $role->id,
                    'password' => self::PASSWORD,
                ]);
            } else {
                if ($user->trashed()) {
                    $user->restore();
                }
                $user
                    ->forceFill([
                        'department' => $departments[$i % count($departments)],
                        'job_title' => $jobTitles[$i % count($jobTitles)],
                        'current_city' => $cities[$i % count($cities)],
                        'status' => 'active',
                        'role_id' => $role->id,
                        'password' => self::PASSWORD,
                    ])
                    ->save();
            }

            $user->syncRoles([$role->name]);
        }
    }
}
