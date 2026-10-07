<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionExpiryService
{
    public function __construct(private readonly PackagePermissionService $packagePermissions) {}

    /**
     * Expire active paid subscriptions past ends_at and ensure Parichay free is active.
     *
     * @return array{expired: int, parichay_activated: int}
     */
    public function expirePaidAndActivateParichay(): array
    {
        $parichayId = Package::query()
            ->where('code', 'PARICHAY_FREE')
            ->where('is_active', true)
            ->where('deleted_at', null)
            ->value('id');

        if ($parichayId <= 0) {
            return ['expired' => 0, 'parichay_activated' => 0];
        }

        $now = now();
        $expiredCount = 0;
        $parichayActivated = 0;

        $userIds = Subscription::query()
            ->where('subscription_status', 'active')
            ->where('ends_at', '!=', null)
            ->where('ends_at', '<', $now)
            ->where('package_id', '!=', $parichayId)
            ->pluck('user_id')
            ->map(static fn($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        foreach ($userIds as $userId) {
            DB::transaction(function () use ($userId, $parichayId, $now, &$expiredCount, &$parichayActivated): void {
                $expired = Subscription::query()
                    ->where('user_id', $userId)
                    ->where('subscription_status', 'active')
                    ->where('ends_at', '!=', null)
                    ->where('ends_at', '<', $now)
                    ->where('package_id', '!=', $parichayId)
                    ->update([
                        'subscription_status' => 'expired',
                        'updated_at' => $now,
                    ]);

                $expiredCount += $expired;

                $parichay = Subscription::query()->where('user_id', $userId)->where('package_id', $parichayId)->first();

                if ($parichay instanceof Subscription) {
                    $parichay
                        ->forceFill([
                            'subscription_status' => 'active',
                            'started_at' => $parichay->started_at ?? $now,
                            'ends_at' => $now->copy()->addYear(),
                            'renewal_source' => 'system_expiry',
                            'updated_at' => $now,
                        ])
                        ->save();
                } else {
                    Subscription::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'user_id' => $userId,
                        'package_id' => $parichayId,
                        'subscription_status' => 'active',
                        'started_at' => $now,
                        'ends_at' => $now->copy()->addYear(),
                        'auto_renew' => false,
                        'renewal_source' => 'system_expiry',
                    ]);
                }

                $parichayActivated++;

                $user = User::query()->find($userId);

                if ($user instanceof User) {
                    $this->packagePermissions->syncCandidatePermissions($user);
                }
            });
        }

        return ['expired' => $expiredCount, 'parichay_activated' => $parichayActivated];
    }
}
