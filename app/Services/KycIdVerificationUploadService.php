<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\GdWebpEncoder;
use App\Support\UserImageStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class KycIdVerificationUploadService
{
    /**
     * Store a single WebP under `{userId}/{folder}/{uuid}.webp` on the user profile images disk.
     *
     * @return array{storage_key: string, public_url: string}
     */
    public function storeWebp(UploadedFile $file, int $userId): array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'image' => ['Image processing is not available on this server (GD / WebP).'],
            ]);
        }

        $tmpPath = $file->getRealPath();

        if ($tmpPath === false || !is_readable($tmpPath)) {
            throw ValidationException::withMessages([
                'image' => ['The image could not be read.'],
            ]);
        }

        $folder = (string) config('kyc_id_verification.folder', 'id_verification');
        $maxEdge = (int) config('kyc_id_verification.max_edge', 2048);
        $quality = (int) config('kyc_id_verification.quality', 85);
        $disk = UserImageStorageUrl::disk();

        try {
            $binary = GdWebpEncoder::encodeScaledWebp($tmpPath, $maxEdge, $quality);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'image' => ['The file could not be processed as an image.'],
            ]);
        }

        $uuid = (string) Str::uuid();
        $storageKey = $userId . '/' . $folder . '/' . $uuid . '.webp';

        $visibility = UserImageStorageUrl::shouldUseSignedKycUrl($storageKey) ? 'private' : 'public';
        Storage::disk($disk)->put($storageKey, $binary, ['visibility' => $visibility]);

        $publicUrl = UserImageStorageUrl::resolvePublicHttpUrl($storageKey);

        if ($publicUrl === null || $publicUrl === '') {
            throw ValidationException::withMessages([
                'image' => ['Could not resolve public URL for upload.'],
            ]);
        }

        return [
            'storage_key' => $storageKey,
            'public_url' => $publicUrl,
        ];
    }

    public function deleteStoredKeyIfOwned(?string $relativeKey, int $userId): void
    {
        if ($relativeKey === null || $relativeKey === '' || str_contains($relativeKey, '://')) {
            return;
        }

        $folder = (string) config('kyc_id_verification.folder', 'id_verification');
        $prefix = $userId . '/' . $folder . '/';

        if (!str_starts_with($relativeKey, $prefix)) {
            return;
        }

        Storage::disk(UserImageStorageUrl::disk())->delete($relativeKey);
    }
}
