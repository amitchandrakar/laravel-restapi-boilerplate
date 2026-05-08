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
];
