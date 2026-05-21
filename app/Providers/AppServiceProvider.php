<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\Handler as AppExceptionHandler;
use App\Jobs\Scout\MakeSearchableOnLowQueue;
use App\Jobs\Scout\RemoveFromSearchOnLowQueue;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Observers\PackageObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\UserObserver;
use App\Policies\SubscriptionPolicy;
use App\Policies\TeamUserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Scout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, AppExceptionHandler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureScoutQueues();

        Gate::before(static function ($user, string $ability): ?bool {
            if ($user instanceof User && $user->hasRole('admin')) {
                return true;
            }

            return null;
        });

        Gate::define('viewAnyTeamMember', [TeamUserPolicy::class, 'viewAnyTeamMember']);
        Gate::define('viewTeamMember', [TeamUserPolicy::class, 'viewTeamMember']);
        Gate::define('createTeamMember', [TeamUserPolicy::class, 'createTeamMember']);
        Gate::define('updateTeamMember', [TeamUserPolicy::class, 'updateTeamMember']);
        Gate::define('deleteTeamMember', [TeamUserPolicy::class, 'deleteTeamMember']);
        Gate::define('viewAdminSubscriptions', [SubscriptionPolicy::class, 'viewAdminSubscriptions']);

        User::observe(UserObserver::class);
        Package::observe(PackageObserver::class);
        Subscription::observe(SubscriptionObserver::class);
    }

    protected function configureScoutQueues(): void
    {
        Scout::makeSearchableUsing(MakeSearchableOnLowQueue::class);
        Scout::removeFromSearchUsing(RemoveFromSearchOnLowQueue::class);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-auth-strict', static function (Request $request): Limit {
            $perMinute = max(1, (int) config('api.throttle.auth_per_minute', 5));

            return Limit::perMinute($perMinute)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-general', static function (Request $request): Limit {
            $perMinute = max(1, (int) config('api.throttle.general_per_minute', 60));

            return Limit::perMinute($perMinute)->by($request->user()?->id ?: $request->ip());
        });
    }
}
