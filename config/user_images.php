<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Filesystem disk
    |--------------------------------------------------------------------------
    |
    | Must match a disk key in config/filesystems.php. Point this disk to S3
    | later while keeping the same relative object keys under {user_id}/SM|MD|ORIGINAL|ICON/.
    |
    */
    'disk' => env('USER_PROFILE_IMAGES_DISK', 'user_profile_images'),

    'max_images_per_user' => 5,

    /** Max upload size in kilobytes (multipart file validation). */
    'max_upload_kb' => (int) env('USER_PROFILE_IMAGE_MAX_KB', 5120),

    /** Stored variant file extension (encoder output). */
    'extension' => 'webp',

    /** Longest edge in pixels (scale down, keep aspect ratio). */
    'sm_max_edge' => (int) env('USER_PROFILE_IMAGE_SM_MAX', 320),
    'md_max_edge' => (int) env('USER_PROFILE_IMAGE_MD_MAX', 960),
    'original_max_edge' => (int) env('USER_PROFILE_IMAGE_ORIGINAL_MAX', 2048),

    /**
     * Square icon for notifications / avatars: width and height in pixels (center-cropped after scale-up to cover).
     */
    'icon_size' => (int) env('USER_PROFILE_IMAGE_ICON_SIZE', 50),

    /** WebP quality 0–100 per variant. */
    'quality' => [
        'sm' => (int) env('USER_PROFILE_IMAGE_QUALITY_SM', 78),
        'md' => (int) env('USER_PROFILE_IMAGE_QUALITY_MD', 82),
        'original' => (int) env('USER_PROFILE_IMAGE_QUALITY_ORIGINAL', 85),
        'icon' => (int) env('USER_PROFILE_IMAGE_QUALITY_ICON', 75),
    ],

    /** Directory names under public/images/uploads/{user_id}/ */
    'folders' => [
        'sm' => 'SM',
        'md' => 'MD',
        'original' => 'ORIGINAL',
        'icon' => env('USER_PROFILE_IMAGE_FOLDER_ICON', 'ICON'),
    ],
];
