<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\TelescopeServiceProvider;

$providers = [AppServiceProvider::class, EventServiceProvider::class];

if (filter_var(env('TELESCOPE_ENABLED', false), FILTER_VALIDATE_BOOL)) {
    $providers[] = TelescopeServiceProvider::class;
}

return $providers;
