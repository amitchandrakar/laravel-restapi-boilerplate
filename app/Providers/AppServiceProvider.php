<?php

declare(strict_types=1);

namespace App\Providers;

use Hashids\Hashids;
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
        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }
}
