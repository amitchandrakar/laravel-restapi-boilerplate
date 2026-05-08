<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\GdWebpEncoder;
use App\Support\UserImageStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Profile image variants (WebP) using ext-gd only — avoids Intervention Image v4,
 * which requires PHP 8.3+ for its source syntax.
 */
final class UserImageUploadService
{
    /**
     * Process multipart upload: ORIGINAL / MD / SM / ICON variants on disk, insert `user_images` row.
     * New rows use `is_profile_photo` false and the next available `sort_order` automatically.
     *
     * @return array<string, mixed> API payload (camelCase keys)
     */
    public function upload(User $user, UploadedFile $file): array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'image' => ['Image processing is not available on this server (GD / WebP).'],
            ]);
        }

        $disk = UserImageStorageUrl::disk();
        $ext = (string) config('user_images.extension', 'webp');
        /** @var array{sm: string, md: string, original: string, icon: string} $folders */
        $folders = config('user_images.folders');
        /** @var array{sm: int, md: int, original: int, icon: int} $quality */
        $quality = config('user_images.quality');

        $tmpPath = $file->getRealPath();
        if ($tmpPath === false || !is_readable($tmpPath)) {
            throw ValidationException::withMessages([
                'image' => ['The image could not be read.'],
            ]);
        }

        return DB::transaction(function () use ($user, $disk, $ext, $folders, $quality, $tmpPath): array {
            $userId = (int) $user->id;
            $max = (int) config('user_images.max_images_per_user', 5);
            $count = (int) DB::table('user_images')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();
            if ($count >= $max) {
                throw ValidationException::withMessages([
                    'image' => ["You can upload at most {$max} images."],
                ]);
            }

            $stemUuid = (string) Str::uuid();
            $filename = $stemUuid . '.' . $ext;

            $smEdge = (int) config('user_images.sm_max_edge', 320);
            $mdEdge = (int) config('user_images.md_max_edge', 960);
            $origEdge = (int) config('user_images.original_max_edge', 2048);
            $iconSize = max(1, min(512, (int) config('user_images.icon_size', 50)));

            try {
                $originalBinary = GdWebpEncoder::encodeScaledWebp($tmpPath, $origEdge, $quality['original']);
                $mdBinary = GdWebpEncoder::encodeScaledWebp($tmpPath, $mdEdge, $quality['md']);
                $smBinary = GdWebpEncoder::encodeScaledWebp($tmpPath, $smEdge, $quality['sm']);
                $iconBinary = GdWebpEncoder::encodeSquareCenterWebp($tmpPath, $iconSize, $quality['icon']);
            } catch (Throwable $e) {
                if (config('app.debug')) {
                    Log::warning('UserImageUploadService: image pipeline failed', [
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                }

                throw ValidationException::withMessages([
                    'image' => ['The file could not be processed as an image.'],
                ]);
            }

            $keyOriginal = $userId . '/' . $folders['original'] . '/' . $filename;
            $keyMd = $userId . '/' . $folders['md'] . '/' . $filename;
            $keySm = $userId . '/' . $folders['sm'] . '/' . $filename;
            $keyIcon = $userId . '/' . $folders['icon'] . '/' . $filename;

            Storage::disk($disk)->put($keyOriginal, $originalBinary, ['visibility' => 'public']);
            Storage::disk($disk)->put($keyMd, $mdBinary, ['visibility' => 'public']);
            Storage::disk($disk)->put($keySm, $smBinary, ['visibility' => 'public']);
            Storage::disk($disk)->put($keyIcon, $iconBinary, ['visibility' => 'public']);

            $resolvedSort = $this->nextSortOrder($userId);

            $urlMd = UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($keyMd) ?? $keyMd);
            $urlSm = UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($keySm) ?? $keySm);
            $urlOrig = UserImageStorageUrl::toAbsoluteHttpUrl(
                UserImageStorageUrl::publicUrl($keyOriginal) ?? $keyOriginal
            );
            $urlIcon = UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($keyIcon) ?? $keyIcon);

            $id = (int) DB::table('user_images')->insertGetId([
                'uuid' => $stemUuid,
                'user_id' => $userId,
                'image_type' => 'profile',
                'image_storage_path' => $keyMd,
                'image_url' => $urlMd,
                'thumbnail_url' => $urlSm,
                'icon_url' => $urlIcon,
                'is_profile_photo' => false,
                'sort_order' => $resolvedSort,
                'is_active' => true,
                'uploaded_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'id' => $id,
                'uuid' => $stemUuid,
                'imageStoragePath' => $keyMd,
                'url' => $urlMd,
                'thumbnailUrl' => $urlSm,
                'originalUrl' => $urlOrig,
                'iconUrl' => $urlIcon,
            ];
        });
    }

    private function nextSortOrder(int $userId): int
    {
        $max = DB::table('user_images')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->max('sort_order');
        if ($max === null) {
            return 0;
        }

        return min(4, (int) $max + 1);
    }
}
