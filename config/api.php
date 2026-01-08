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
        'include_trace_in_debug' => env('APP_DEBUG', false),
        'snake_case_keys' => false, // Convert response keys to snake_case
    ],
];
