<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Postman collection generation
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan postman:generate`. Defaults match DemoAuthUsersSeeder
    | and DemoUsersSeeder so login works after migrate:fresh --seed.
    |
    */

    'base_url' => env('POSTMAN_BASE_URL'),

    'admin_username' => env('POSTMAN_ADMIN_USERNAME', 'admin@example.com'),

    'admin_password' => env('POSTMAN_ADMIN_PASSWORD', '1234567890'),

    'candidate_username' => env('POSTMAN_CANDIDATE_USERNAME', 'candidate.parichay@example.com'),

    'candidate_password' => env('POSTMAN_CANDIDATE_PASSWORD', '1234567890'),
];
