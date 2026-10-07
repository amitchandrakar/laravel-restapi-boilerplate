<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (HTTP v1)
    |--------------------------------------------------------------------------
    |
    | Place the service account JSON from Firebase Console on disk and set
    | FIREBASE_CREDENTIALS to its absolute path. Optionally set FIREBASE_PROJECT_ID
    | (otherwise project_id inside the JSON is used).
    |
    | Admin notification_settings.push_enabled still gates sends when a row exists.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
    'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'push_enabled' => filter_var(env('FIREBASE_PUSH_ENABLED', true), FILTER_VALIDATE_BOOL),
];
