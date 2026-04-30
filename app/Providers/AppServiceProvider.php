<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Observers\PackageObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\UserObserver;
use Hashids\Hashids;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('hashid', function () {
            $salt = config('hashids.salt', config('app.key', ''));
            $minHashLength = (int) config('hashids.min_length', 0);
            $alphabet = config('hashids.alphabet', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

            return new Hashids($salt, $minHashLength, $alphabet);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function ($user, string $ability): ?bool {
            if ($user instanceof User && $user->hasRole('admin')) {
                return true;
            }

            return null;
        });

        User::observe(UserObserver::class);
        Package::observe(PackageObserver::class);
        Subscription::observe(SubscriptionObserver::class);
    }
}
