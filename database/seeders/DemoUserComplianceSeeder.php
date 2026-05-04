<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoUserComplianceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $defaultPackageId = (int) DB::table('packages')
            ->where('is_active', true)
            ->where('is_default_registration', true)
            ->value('id');
        if ($defaultPackageId === 0) {
            $defaultPackageId = (int) DB::table('packages')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('id');
        }
        if ($defaultPackageId === 0) {
            return;
        }

        $users = DB::table('users')->select('id')->get();
        foreach ($users as $user) {
            $userId = (int) $user->id;

            $hasSubscription = DB::table('subscriptions')->where('user_id', $userId)->exists();
            if (!$hasSubscription) {
                $subscriptionId = $this->ensureSubscription($userId, $defaultPackageId, $now);
                if ($subscriptionId > 0) {
                    $this->ensureMembershipHistory($userId, $defaultPackageId, $subscriptionId, $now);
                }
            }

            $this->ensureVerificationDocument($userId, $now);
        }
    }

    private function ensureSubscription(int $userId, int $packageId, \Carbon\CarbonInterface $now): int
    {
        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $packageId],
            [
                'uuid' => (string) Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'auto_renew' => false,
                'renewal_source' => 'system',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        return (int) DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->value('id');
    }

    private function ensureMembershipHistory(
        int $userId,
        int $packageId,
        int $subscriptionId,
        \Carbon\CarbonInterface $now
    ): void {
        $exists = DB::table('user_membership_history')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->where('action_type', 'started')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('user_membership_history')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'package_id' => $packageId,
            'subscription_id' => $subscriptionId,
            'action_type' => 'started',
            'amount' => null,
            'currency' => 'INR',
            'action_by' => null,
            'action_source' => 'system',
            'notes' => 'Seeded demo membership history.',
            'created_at' => $now,
        ]);
    }

    private function ensureVerificationDocument(int $userId, \Carbon\CarbonInterface $now): void
    {
        $exists = DB::table('user_verification_documents')
            ->where('user_id', $userId)
            ->where('document_type', 'aadhaar')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('user_verification_documents')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'document_type' => 'aadhaar',
            'document_number_masked' => 'XXXX-XXXX-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'document_front_url' => 'https://example.com/docs/demo-front-' . $userId . '.jpg',
            'document_back_url' => 'https://example.com/docs/demo-back-' . $userId . '.jpg',
            'selfie_url' => 'https://example.com/docs/demo-selfie-' . $userId . '.jpg',
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
            'submitted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
