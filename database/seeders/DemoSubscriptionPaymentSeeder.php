<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSubscriptionPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedForUserAndPackage('candidate@example.com', 'PARICHAY_FREE', 0.0);
        $this->seedForUserAndPackage('p.candidate@example.com', 'RISHTA_PRO', 730.0);
    }

    private function seedForUserAndPackage(string $email, string $packageCode, float $amount): void
    {
        $now = now();

        $userId = (int) DB::table('users')->where('email', $email)->value('id');
        $packageId = (int) DB::table('packages')->where('code', $packageCode)->value('id');
        if ($userId === 0 || $packageId === 0) {
            return;
        }

        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $packageId],
            [
                'uuid' => (string) Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'auto_renew' => true,
                'renewal_source' => 'manual',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $subscriptionId = (int) DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->value('id');
        if ($subscriptionId === 0) {
            return;
        }

        $existingPaymentId = (int) DB::table('payments')
            ->where('user_id', $userId)
            ->where('subscription_id', $subscriptionId)
            ->where('package_id', $packageId)
            ->value('id');

        if ($existingPaymentId === 0) {
            DB::table('payments')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'package_id' => $packageId,
                'gateway_name' => 'demo',
                'gateway_order_id' => 'demo-order-' . Str::lower(Str::random(10)),
                'gateway_payment_id' => 'demo-pay-' . Str::lower(Str::random(10)),
                'gateway_reference_id' => 'demo-ref-' . Str::lower(Str::random(10)),
                'amount' => $amount,
                'currency' => 'INR',
                'payment_status' => 'success',
                'payment_method' => 'manual',
                'paid_at' => $now,
                'raw_response_json' => json_encode(['source' => 'seeder'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existingPaymentId = (int) DB::table('payments')
                ->where('user_id', $userId)
                ->where('subscription_id', $subscriptionId)
                ->where('package_id', $packageId)
                ->value('id');
        }

        DB::table('subscriptions')
            ->where('id', $subscriptionId)
            ->update([
                'last_payment_id' => $existingPaymentId > 0 ? $existingPaymentId : null,
                'updated_at' => $now,
            ]);
    }
}
