<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resolves `user_images` path values to public URLs and derives ORIGINAL keys from MD keys.
 *
 * Relative keys (no "://") are resolved via the configured disk for S3-ready migrations.
 */
final class UserImageStorageUrl
{
    public static function disk(): string
    {
        return (string) config('user_images.disk', 'user_profile_images');
    }

    public static function isRelativeStorageKey(string $value): bool
    {
        return $value !== '' && !str_contains($value, '://');
    }

    public static function publicUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }
        if (!self::isRelativeStorageKey($stored)) {
            return $stored;
        }

        return Storage::disk(self::disk())->url($stored);
    }

    /**
     * Ensure a browser-loadable absolute URL (http/https) for persisted API values.
     * Prefixes {@see config('app.url')} when the disk returns a root-relative path.
     */
    public static function toAbsoluteHttpUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }
        if (str_contains($url, '://')) {
            return $url;
        }

        $base = rtrim((string) config('app.url', 'http://localhost'), '/');

        return $base . (str_starts_with($url, '/') ? $url : '/' . $url);
    }

    /**
     * From an MD relative key `{userId}/MD/{uuid}.ext`, build `{userId}/ORIGINAL/{uuid}.ext`.
     */
    public static function originalKeyFromMdKey(string $mdKey): ?string
    {
        return self::variantKeyFromMdKey($mdKey, 'original');
    }

    /**
     * From an MD relative key `{userId}/MD/{uuid}.ext`, build `{userId}/{folder}/{uuid}.ext`.
     *
     * @param  'sm'|'md'|'original'|'icon'  $variant
     */
    public static function variantKeyFromMdKey(string $mdKey, string $variant): ?string
    {
        $folders = config('user_images.folders');
        if (!is_array($folders) || !isset($folders['md'], $folders[$variant])) {
            return null;
        }
        $md = (string) $folders['md'];
        $target = (string) $folders[$variant];
        if ($target === '') {
            return null;
        }
        $needle = '/' . $md . '/';
        if (!str_contains($mdKey, $needle)) {
            return null;
        }

        return str_replace($needle, '/' . $target . '/', $mdKey);
    }

    public static function iconKeyFromMdKey(string $mdKey): ?string
    {
        return self::variantKeyFromMdKey($mdKey, 'icon');
    }

    public static function iconPublicUrl(?string $mdStored): ?string
    {
        if ($mdStored === null || $mdStored === '') {
            return null;
        }
        $key = self::iconKeyFromMdKey($mdStored);
        if ($key === null) {
            return null;
        }

        return self::publicUrl($key);
    }

    public static function originalPublicUrl(?string $mdStored): ?string
    {
        if ($mdStored === null || $mdStored === '') {
            return null;
        }
        $key = self::originalKeyFromMdKey($mdStored);
        if ($key === null) {
            return null;
        }

        return self::publicUrl($key);
    }
}
