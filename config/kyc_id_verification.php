<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ID verification uploads (Aadhaar / selfie) — same disk as profile images.
    | Paths: public/images/uploads/{user_id}/id_verification/{uuid}.webp
    |--------------------------------------------------------------------------
    */
    'folder' => env('KYC_ID_VERIFICATION_FOLDER', 'id_verification'),

    'max_edge' => (int) env('KYC_ID_VERIFICATION_MAX_EDGE', 2048),

    'quality' => (int) env('KYC_ID_VERIFICATION_WEBP_QUALITY', 85),

    'max_upload_kb' => (int) env('KYC_ID_VERIFICATION_MAX_KB', 5120),

    /**
     * When true and the profile images disk is S3, KYC URLs use short-lived signed links
     * instead of public URLs (app-plan §16).
     */
    'use_signed_urls' => filter_var(env('KYC_USE_SIGNED_URLS', false), FILTER_VALIDATE_BOOL),

    'signed_url_minutes' => (int) env('KYC_SIGNED_URL_MINUTES', 15),
];
