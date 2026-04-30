<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\PackagePermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAuthUsersSeeder extends Seeder
{
    private const PASSWORD = '123456';

    public function __construct(private readonly PackagePermissionService $packagePermissionService) {}

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
        $this->upsertSubscriptionForUser($candidate->id, 'PARICHAY_FREE');
        $this->packagePermissionService->syncCandidatePermissions($candidate);

        $premiumCandidate = $this->upsertUser(
            email: 'p.candidate@example.com',
            firstName: 'Premium',
            lastName: 'Candidate',
            status: 'active'
        );
        $this->assignRoleIfExists($premiumCandidate, 'candidate', $guard);
        $this->upsertSubscriptionForUser($premiumCandidate->id, 'RISHTA_PRO');
        $this->packagePermissionService->syncCandidatePermissions($premiumCandidate);
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

    private function upsertSubscriptionForUser(int $userId, string $packageCode): void
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
}
