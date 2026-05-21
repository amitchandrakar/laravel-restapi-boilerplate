<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ForgotPasswordRequestedEvent;
use App\Events\PackageCreatedEvent;
use App\Events\PackageUpdatedEvent;
use App\Events\TeamMemberLifecycleEvent;
use App\Events\UserCreatedEvent;
use App\Events\UserLifecycleEvent;
use App\Listeners\ForgotPasswordRequestedListener;
use App\Listeners\PackageCreatedListener;
use App\Listeners\PackageUpdatedListener;
use App\Listeners\TeamMemberLifecycleListener;
use App\Listeners\UserCreatedListener;
use App\Listeners\UserLifecycleListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreatedEvent::class => [UserCreatedListener::class],
        UserLifecycleEvent::class => [UserLifecycleListener::class],
        ForgotPasswordRequestedEvent::class => [ForgotPasswordRequestedListener::class],
        PackageCreatedEvent::class => [PackageCreatedListener::class],
        PackageUpdatedEvent::class => [PackageUpdatedListener::class],
        TeamMemberLifecycleEvent::class => [TeamMemberLifecycleListener::class],
    ];
}
