<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\PackagePermissionService;

class SubscriptionObserver
{
    public function __construct(private readonly PackagePermissionService $packagePermissionService) {}

    public function created(Subscription $subscription): void
    {
        $this->syncForUser((int) $subscription->user_id);
    }

    public function updated(Subscription $subscription): void
    {
        $this->syncForUser((int) $subscription->user_id);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->syncForUser((int) $subscription->user_id);
    }

    private function syncForUser(int $userId): void
    {
        /** @var User|null $user */
        $user = User::query()->find($userId);
        if (!$user instanceof User) {
            return;
        }

        $this->packagePermissionService->syncCandidatePermissions($user);
    }
}
