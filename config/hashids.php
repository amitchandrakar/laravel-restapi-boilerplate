<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hashids salt (use app key for consistency with existing hashes)
    |--------------------------------------------------------------------------
    */
    'salt' => env('HASHIDS_SALT', env('APP_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Minimum hash length (0 = no padding)
    |--------------------------------------------------------------------------
    */
    'min_length' => (int) env('HASHIDS_MIN_LENGTH', 0),

    /*
    |--------------------------------------------------------------------------
    | Alphabet for encoding (must not have duplicates)
    |--------------------------------------------------------------------------
    */
    'alphabet' => env('HASHIDS_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
];
