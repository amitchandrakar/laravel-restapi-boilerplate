<?php

declare(strict_types=1);

/**
 * Application cache TTLs (app-plan §15). Keys use App\Support\CacheKeys.
 */
return [
    'user_permissions_seconds' => (int) env('CACHE_TTL_USER_PERMISSIONS', 600),
    'master_data_seconds' => (int) env('CACHE_TTL_MASTER_DATA', 86400),
    'featured_profiles_seconds' => (int) env('CACHE_TTL_FEATURED_PROFILES', 300),
    'dashboard_metrics_seconds' => (int) env('CACHE_TTL_DASHBOARD_METRICS', 3600),
    'dashboard_health_seconds' => (int) env('CACHE_TTL_DASHBOARD_HEALTH', 3600),
    'profile_options_seconds' => (int) env('CACHE_TTL_PROFILE_OPTIONS', 3600),
    'site_settings_seconds' => (int) env('CACHE_TTL_SITE_SETTINGS', 3600),
    'admin_roles_seconds' => (int) env('CACHE_TTL_ADMIN_ROLES', 3600),
    'legal_pages_seconds' => (int) env('CACHE_TTL_LEGAL_PAGES', 600),
    'registration_options_seconds' => (int) env('CACHE_TTL_REGISTRATION_OPTIONS', 600),
];
