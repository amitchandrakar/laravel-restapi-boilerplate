<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserImage;
use App\Support\UserProfilePhotos;
use Illuminate\Support\Facades\DB;

final class UserImageManageService
{
    /**
     * Mark one gallery image as the profile photo; clears the flag on other active rows for this user.
     *
     * @return array<string, mixed>|null when the image does not exist or is not owned by the candidate
     */
    public function setProfilePhoto(User $candidate, string $imageUuid): ?array
    {
        return DB::transaction(function () use ($candidate, $imageUuid): ?array {
            /** @var UserImage|null $image */
            $image = UserImage::query()
                ->where('uuid', $imageUuid)
                ->where('user_id', $candidate->id)
                ->where('is_active', true)
                ->first();

            if ($image === null) {
                return null;
            }

            UserImage::query()
                ->where('user_id', $candidate->id)
                ->where('id', '!=', $image->id)
                ->update([
                    'is_profile_photo' => false,
                    'updated_at' => now(),
                ]);

            $image->is_profile_photo = true;
            $image->save();

            return UserProfilePhotos::mapStorageRow(
                (object) [
                    'id' => $image->id,
                    'uuid' => $image->uuid,
                    'image_type' => $image->image_type,
                    'image_url' => $image->image_url,
                    'image_storage_path' => $image->image_storage_path,
                    'thumbnail_url' => $image->thumbnail_url,
                    'icon_url' => $image->icon_url,
                    'is_profile_photo' => $image->is_profile_photo,
                    'sort_order' => $image->sort_order,
                ]
            );
        });
    }

    /**
     * Soft-delete a user image row (files on disk are left in place).
     *
     * @return array{id: int, uuid: string}|null when not found / not owned
     */
    public function softDeletePhoto(User $candidate, string $imageUuid): ?array
    {
        return DB::transaction(function () use ($candidate, $imageUuid): ?array {
            /** @var UserImage|null $image */
            $image = UserImage::query()
                ->where('uuid', $imageUuid)
                ->where('user_id', $candidate->id)
                ->where('is_active', true)
                ->first();

            if ($image === null) {
                return null;
            }

            $id = (int) $image->id;
            $uuid = (string) $image->uuid;
            $image->delete();

            return ['id' => $id, 'uuid' => $uuid];
        });
    }
}
