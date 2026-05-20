<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLifecycleEvent;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserLifecycleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserLifecycleListener implements ShouldQueue
{
    public function handle(UserLifecycleEvent $event): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $adminRole = Role::query()->where('name', 'admin')->where('guard_name', $guard)->first();

        if (!$adminRole instanceof Role) {
            return;
        }

        /** @var Collection<int, User> $admins */
        $admins = User::query()
            ->whereHas('roles', static function ($query) use ($adminRole): void {
                $query->where('id', $adminRole->id);
            })
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new UserLifecycleNotification($event->user, $event->action));
        }
    }
}
