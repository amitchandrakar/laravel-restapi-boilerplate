<?php

declare(strict_types=1);

/**
 * Application cache TTLs (app-plan §15). Keys use App\Support\CacheKeys.
 */
return [
    'user_permissions_seconds' => (int) env('CACHE_TTL_USER_PERMISSIONS', 600),
    'master_data_seconds' => (int) env('CACHE_TTL_MASTER_DATA', 86400),
    'featured_profiles_seconds' => (int) env('CACHE_TTL_FEATURED_PROFILES', 300),
    'dashboard_metrics_seconds' => (int) env('CACHE_TTL_DASHBOARD_METRICS', 900),
    'profile_options_seconds' => (int) env('CACHE_TTL_PROFILE_OPTIONS', 3600),
];
