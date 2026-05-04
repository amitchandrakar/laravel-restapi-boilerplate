<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\UserImageStorageUrl;
use GdImage;
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
                $originalBinary = $this->encodeVariantWebp($tmpPath, $origEdge, $quality['original']);
                $mdBinary = $this->encodeVariantWebp($tmpPath, $mdEdge, $quality['md']);
                $smBinary = $this->encodeVariantWebp($tmpPath, $smEdge, $quality['sm']);
                $iconBinary = $this->encodeSquareIconWebp($tmpPath, $iconSize, $quality['icon']);
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

    /**
     * Decode from disk, apply common EXIF JPEG orientation, scale down by longest edge, encode WebP.
     */
    private function encodeVariantWebp(string $path, int $maxEdge, int $quality): string
    {
        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('empty file');
        }

        $im = @imagecreatefromstring($binary);
        if (!$im instanceof GdImage) {
            throw new \RuntimeException('decode failed');
        }

        $im = $this->applyExifOrientation($im, $path);
        $im = $this->scaleDownMaxEdge($im, $maxEdge);

        return $this->toWebpString($im, $quality);
    }

    /**
     * Square WebP icon: scale so the image covers {@see $edge}×{@see $edge}, then center-crop.
     */
    private function encodeSquareIconWebp(string $path, int $edge, int $quality): string
    {
        if ($edge < 1) {
            throw new \RuntimeException('invalid icon size');
        }

        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('empty file');
        }

        $im = @imagecreatefromstring($binary);
        if (!$im instanceof GdImage) {
            throw new \RuntimeException('decode failed');
        }

        $im = $this->applyExifOrientation($im, $path);
        $im = $this->scaleAndCropCenterSquare($im, $edge);

        return $this->toWebpString($im, $quality);
    }

    private function scaleAndCropCenterSquare(GdImage $src, int $edge): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        $scale = max($edge / $w, $edge / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $scaled = imagecreatetruecolor($nw, $nh);
        if (!$scaled instanceof GdImage) {
            imagedestroy($src);
            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = (int) imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);
        imagealphablending($scaled, true);
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $sx = (int) max(0, ($nw - $edge) / 2);
        $sy = (int) max(0, ($nh - $edge) / 2);
        $cropW = min($edge, $nw);
        $cropH = min($edge, $nh);

        $out = imagecreatetruecolor($edge, $edge);
        if (!$out instanceof GdImage) {
            imagedestroy($scaled);
            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($out, false);
        imagesavealpha($out, true);
        $transparentOut = (int) imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $transparentOut);
        imagealphablending($out, true);
        imagecopy($out, $scaled, 0, 0, $sx, $sy, $cropW, $cropH);
        imagedestroy($scaled);

        return $out;
    }

    private function applyExifOrientation(GdImage $im, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $im;
        }

        $info = @getimagesize($path);
        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            return $im;
        }

        $exif = @exif_read_data($path);
        if (!is_array($exif) || !isset($exif['Orientation'])) {
            return $im;
        }

        $angle = match ((int) $exif['Orientation']) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => null,
        };

        if ($angle === null) {
            return $im;
        }

        $bg = imagecolorallocatealpha($im, 0, 0, 0, 127);
        $rotated = @imagerotate($im, $angle, $bg);
        if (!$rotated instanceof GdImage) {
            return $im;
        }

        imagedestroy($im);

        return $rotated;
    }

    private function scaleDownMaxEdge(GdImage $src, int $maxEdge): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        if ($w <= $maxEdge && $h <= $maxEdge) {
            return $src;
        }

        $ratio = min($maxEdge / $w, $maxEdge / $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        if (!$dst instanceof GdImage) {
            imagedestroy($src);
            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = (int) imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    private function toWebpString(GdImage $im, int $quality): string
    {
        $q = max(0, min(100, $quality));
        ob_start();
        imagewebp($im, null, $q);
        $bin = ob_get_clean();
        imagedestroy($im);

        if (!is_string($bin) || $bin === '') {
            throw new \RuntimeException('webp encode failed');
        }

        return $bin;
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
