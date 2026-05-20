<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Active gallery rows for a candidate, same shape as `sections.photos` in {@see CandidateUserResource}.
 *
 * `image_url` / `thumbnail_url` / `icon_url` in DB are absolute http(s) URLs for hosted files or external URLs.
 * `image_storage_path` is the canonical relative MD key on the profile-images disk when the file lives there.
 */
final class UserProfilePhotos
{
    /**
     * @return list<array{id: int, uuid: string, type: string, url: string, thumbnailUrl: ?string, originalUrl: ?string, iconUrl: ?string, imageStoragePath: ?string, isProfilePhoto: bool, sortOrder: int}>
     */
    public static function listForUser(User $user): array
    {
        return DB::table('user_images')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get([
                'id',
                'uuid',
                'image_type',
                'image_url',
                'image_storage_path',
                'thumbnail_url',
                'icon_url',
                'is_profile_photo',
                'sort_order',
            ])
            ->map(static function (object $row): array {
                return self::mapStorageRow($row);
            })
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, uuid: string, type: string, url: string, thumbnailUrl: ?string, originalUrl: ?string, iconUrl: ?string, imageStoragePath: ?string, isProfilePhoto: bool, sortOrder: int}
     */
    public static function mapStorageRow(object $row): array
    {
        $imageUrlRaw = (string) data_get($row, 'image_url', '');
        $thumbRaw = data_get($row, 'thumbnail_url');
        $iconRaw = data_get($row, 'icon_url');
        $storagePath = data_get($row, 'image_storage_path');

        $mdRelative = is_string($storagePath) && $storagePath !== '' ? $storagePath : null;

        if ($mdRelative === null && $imageUrlRaw !== '' && !str_contains($imageUrlRaw, '://')) {
            $mdRelative = $imageUrlRaw;
        }

        $urlResolved = UserImageStorageUrl::toAbsoluteHttpUrl(
            UserImageStorageUrl::publicUrl($imageUrlRaw) ?? ($imageUrlRaw !== '' ? $imageUrlRaw : null)
        );
        $url = (string) ($urlResolved ?? '');
        $thumbnailUrl = UserImageStorageUrl::toAbsoluteHttpUrl(
            UserImageStorageUrl::publicUrl(is_string($thumbRaw) ? $thumbRaw : null)
        );
        $iconUrl = UserImageStorageUrl::toAbsoluteHttpUrl(
            UserImageStorageUrl::publicUrl(is_string($iconRaw) && $iconRaw !== '' ? $iconRaw : null)
        );
        $originalUrl =
            $mdRelative !== null
                ? UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::originalPublicUrl($mdRelative))
                : null;

        $imageStoragePath = is_string($storagePath) && $storagePath !== '' ? $storagePath : null;

        return [
            'id' => (int) data_get($row, 'id'),
            'uuid' => (string) data_get($row, 'uuid', ''),
            'type' => (string) data_get($row, 'image_type', ''),
            'url' => (string) $url,
            'thumbnailUrl' => $thumbnailUrl,
            'originalUrl' => $originalUrl,
            'iconUrl' => $iconUrl,
            'imageStoragePath' => $imageStoragePath,
            'isProfilePhoto' => (bool) data_get($row, 'is_profile_photo', false),
            'sortOrder' => (int) data_get($row, 'sort_order', 0),
        ];
    }
}
