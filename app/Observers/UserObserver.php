<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\UserCreatedEvent;
use App\Events\UserLifecycleEvent;
use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (empty($user->uuid)) {
            $user->uuid = (string) Str::uuid();
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        UserCreatedEvent::dispatch($user);
        UserLifecycleEvent::dispatch($user, 'created');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        UserLifecycleEvent::dispatch($user, 'updated');
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        UserLifecycleEvent::dispatch($user, 'deleted');
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
