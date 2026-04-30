<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAuthUsersSeeder extends Seeder
{
    private const PASSWORD = '123456';

    public function run(): void
    {
        $guard = (string) config('auth.defaults.guard', RbacSeeder::GUARD);

        $admin = $this->upsertUser(
            email: 'admin@example.com',
            firstName: 'System',
            lastName: 'Admin',
            status: 'active'
        );
        $this->assignRoleIfExists($admin, 'admin', $guard);

        $reviewer = $this->upsertUser(
            email: 'reviewer@example.com',
            firstName: 'Staff',
            lastName: 'Reviewer',
            status: 'active'
        );
        $this->assignRoleIfExists($reviewer, 'reviewer', $guard);

        $candidate = $this->upsertUser(
            email: 'candidate@example.com',
            firstName: 'Demo',
            lastName: 'Candidate',
            status: 'active'
        );
        $this->assignRoleIfExists($candidate, 'candidate', $guard);

        $premiumCandidate = $this->upsertUser(
            email: 'p.candidate@example.com',
            firstName: 'Premium',
            lastName: 'Candidate',
            status: 'active'
        );
        $this->assignRoleIfExists($premiumCandidate, 'candidate', $guard);

        $this->upsertPremiumMembership($premiumCandidate->id);
    }

    private function upsertUser(string $email, string $firstName, string $lastName, string $status): User
    {
        $user = User::withTrashed()->where('email', $email)->first();
        if ($user === null) {
            /** @var User $user */
            $user = User::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => self::PASSWORD,
                'status' => $status,
            ]);

            return $user;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => self::PASSWORD,
            'status' => $status,
        ]);
        $user->save();

        return $user;
    }

    private function assignRoleIfExists(User $user, string $roleName, string $guard): void
    {
        if (!Role::query()->where('name', $roleName)->where('guard_name', $guard)->exists()) {
            return;
        }

        $user->syncRoles([$roleName]);
    }

    private function upsertPremiumMembership(int $userId): void
    {
        $now = now();
        $packageId = (int) DB::table('packages')->where('code', 'PREMIUM')->value('id');
        if ($packageId === 0) {
            $packageId = (int) DB::table('packages')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => 'Premium',
                'code' => 'PREMIUM',
                'description' => 'Premium demo package for p.candidate@example.com',
                'duration_days' => 365,
                'price' => 4999.00,
                'discounted_price' => 2999.00,
                'currency' => 'INR',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
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
}
