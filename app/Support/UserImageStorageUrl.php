<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;
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
     * Resolve a stored relative disk key or passthrough URL to a browser-loadable absolute URL.
     */
    public static function resolvePublicHttpUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $url = self::publicUrl($stored);

        return self::toAbsoluteHttpUrl($url ?? $stored);
    }

    /**
     * Resolve a KYC / ID verification asset URL (signed when configured for private S3).
     */
    public static function resolveKycDocumentUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        if (!self::isRelativeStorageKey($stored)) {
            return self::toAbsoluteHttpUrl($stored);
        }

        if (self::shouldUseSignedKycUrl($stored)) {
            $signed = self::temporaryUrl($stored);
            if ($signed !== null) {
                return $signed;
            }
        }

        return self::resolvePublicHttpUrl($stored);
    }

    public static function shouldUseSignedKycUrl(string $relativeKey): bool
    {
        if (!filter_var(config('kyc_id_verification.use_signed_urls', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        $driver = (string) config('filesystems.disks.' . self::disk() . '.driver', 'local');

        return $driver === 's3' || self::isKycVerificationKey($relativeKey);
    }

    public static function isKycVerificationKey(string $relativeKey): bool
    {
        $folder = (string) config('kyc_id_verification.folder', 'id_verification');

        return str_contains($relativeKey, '/' . trim($folder, '/') . '/');
    }

    public static function temporaryUrl(string $relativeKey): ?string
    {
        if (!self::isRelativeStorageKey($relativeKey)) {
            return null;
        }

        $driver = (string) config('filesystems.disks.' . self::disk() . '.driver', 'local');
        if ($driver !== 's3') {
            return null;
        }

        $disk = Storage::disk(self::disk());

        $minutes = max(1, (int) config('kyc_id_verification.signed_url_minutes', 15));

        try {
            return $disk->temporaryUrl($relativeKey, Carbon::now()->addMinutes($minutes));
        } catch (\Throwable) {
            return null;
        }
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
