<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\UserCreatedEvent;
use App\Events\UserLifecycleEvent;
use App\Jobs\SyncProfileToAlgolia;
use App\Models\User;
use App\Support\ScoutConfig;
use App\Support\SeedingGuard;
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
        if (SeedingGuard::active()) {
            return;
        }

        UserCreatedEvent::dispatch($user);
        UserLifecycleEvent::dispatch($user, 'created');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (SeedingGuard::active()) {
            return;
        }

        UserLifecycleEvent::dispatch($user, 'updated');

        if (
            ScoutConfig::usesAlgolia() &&
            $user->isCandidateRole() &&
            $user->wasChanged([
                'profile_status',
                'published_at',
                'is_featured',
                'deleted_at',
                'gender',
                'date_of_birth',
                'current_city',
                'occupation',
                'last_name',
            ])
        ) {
            SyncProfileToAlgolia::dispatch((int) $user->id);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if (SeedingGuard::active()) {
            return;
        }

        UserLifecycleEvent::dispatch($user, 'deleted');

        if (ScoutConfig::usesAlgolia() && $user->isCandidateRole()) {
            SyncProfileToAlgolia::dispatch((int) $user->id);
        }
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
