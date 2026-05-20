<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'prefix' => env('API_PREFIX', 'api'),

    'version' => env('API_VERSION', 'v1'),

    'versions' => [
        'v1' => [
            'namespace' => 'App\Http\Controllers\Api\V1',
            'middleware' => ['api'],
            'routes' => base_path('routes/api/v1.php'),
        ],
        // Future versions
        // 'v2' => [
        //     'namespace' => 'App\Http\Controllers\Api\V2',
        //     'middleware' => ['api'],
        //     'routes' => base_path('routes/api/v2.php'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => env('API_RATE_LIMIT', 60),

    'throttle' => [
        'attempts' => env('API_THROTTLE_ATTEMPTS', 5),
        'decay_minutes' => env('API_THROTTLE_DECAY_MINUTES', 1),
        'auth_per_minute' => (int) env('API_AUTH_RATE_LIMIT_PER_MINUTE', 5),
        'general_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication (Sanctum + lockout)
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'token_expiry_days' => (int) env('API_AUTH_TOKEN_EXPIRY_DAYS', 30),
        'lockout' => [
            'max_attempts' => (int) env('API_AUTH_LOCKOUT_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('API_AUTH_LOCKOUT_DECAY_MINUTES', 15),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'per_page_param' => 'per_page',
        'page_param' => 'page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Parameters
    |--------------------------------------------------------------------------
    */

    'query' => [
        'filter_param' => 'filter',
        'sort_param' => 'sort',
        'include_param' => 'include',
        'search_param' => 'search',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Deprecation
    |--------------------------------------------------------------------------
    */

    'deprecation' => [
        'v1' => [
            'sunset_date' => null, // '2025-12-31'
            'message' => 'This API version is deprecated. Please migrate to v2.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Formatting
    |--------------------------------------------------------------------------
    */

    'response' => [
        'version' => env('API_RESPONSE_VERSION', '1.0.0'),
        'include_trace_in_debug' => env('APP_DEBUG', false),
        'snake_case_keys' => false, // Convert response keys to snake_case
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine-Readable Error Codes
    |--------------------------------------------------------------------------
    */

    'error_codes' => [
        'NOT_FOUND' => 'NOT_FOUND',
        'VALIDATION_ERROR' => 'VALIDATION_ERROR',
        'UNAUTHORIZED' => 'UNAUTHORIZED',
        'FORBIDDEN' => 'FORBIDDEN',
        'METHOD_NOT_ALLOWED' => 'METHOD_NOT_ALLOWED',
        'TOO_MANY_REQUESTS' => 'TOO_MANY_REQUESTS',
        'CONFLICT' => 'CONFLICT',
        'DB_ERROR' => 'DB_ERROR',
        'INTERNAL_SERVER_ERROR' => 'INTERNAL_SERVER_ERROR',
        'BAD_REQUEST' => 'BAD_REQUEST',
    ],
];
