<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\GdWebpEncoder;
use App\Support\UserImageStorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class TeamUserProfilePhotoService
{
    /**
     * Store a single WebP profile photo for a team user and update `profile_photo_url`.
     */
    public function store(User $user, UploadedFile $file): string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'profile_photo' => ['Image processing is not available on this server (GD / WebP).'],
            ]);
        }

        $tmpPath = $file->getRealPath();

        if ($tmpPath === false || !is_readable($tmpPath)) {
            throw ValidationException::withMessages([
                'profile_photo' => ['The profile photo could not be read.'],
            ]);
        }

        $disk = UserImageStorageUrl::disk();
        $ext = (string) config('user_images.extension', 'webp');
        $quality = (int) config('user_images.quality.md', 85);
        $edge = (int) config('user_images.md_max_edge', 960);
        $filename = (string) $user->uuid . '.' . $ext;
        $path = 'team-users/' . $user->uuid . '/' . $filename;

        try {
            $binary = GdWebpEncoder::encodeScaledWebp($tmpPath, $edge, $quality);
        } catch (Throwable $e) {
            Log::error('TeamUserProfilePhotoService: encode failed', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'profile_photo' => ['The profile photo could not be processed.'],
            ]);
        }

        Storage::disk($disk)->put($path, $binary);
        $url = UserImageStorageUrl::publicUrl($path);

        $user->forceFill(['profile_photo_url' => $url])->save();

        Log::info('TeamUserProfilePhotoService: profile photo stored', [
            'user_id' => $user->id,
            'path' => $path,
        ]);

        return $url;
    }
}
