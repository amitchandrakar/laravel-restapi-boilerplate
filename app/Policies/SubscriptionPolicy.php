<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class SubscriptionPolicy
{
    public function viewAdminSubscriptions(User $actor): bool
    {
        return $actor->can('admin.subscriptions.view');
    }
}
