<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        return (int) $actor->id === (int) $target->id;
    }

    public function view(User $actor, User $target): bool
    {
        if ((int) $actor->id === (int) $target->id) {
            return true;
        }

        return $actor->can('admin.users.view') || $actor->can('admin.candidates.view');
    }
}
