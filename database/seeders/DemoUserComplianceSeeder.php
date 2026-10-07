<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonInterface;
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

        // Remove seeded placeholder KYC rows so members can upload real documents.
        // Fake example.com URLs blocked submit (pending lock) and broke ID-check previews.
        $this->removePlaceholderVerificationDocuments();

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
        }
    }

    /**
     * Demo KYC used to insert pending rows with https://example.com/... URLs.
     * Those cannot be displayed on device and block real multipart submit while pending.
     */
    private function removePlaceholderVerificationDocuments(): void
    {
        DB::table('user_verification_documents')
            ->where(static function ($query): void {
                $query
                    ->where('document_front_url', 'like', '%example.com%')
                    ->orWhere('document_back_url', 'like', '%example.com%')
                    ->orWhere('selfie_url', 'like', '%example.com%');
            })
            ->delete();
    }

    private function ensureSubscription(int $userId, int $packageId, CarbonInterface $now): int
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
        CarbonInterface $now
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
}
