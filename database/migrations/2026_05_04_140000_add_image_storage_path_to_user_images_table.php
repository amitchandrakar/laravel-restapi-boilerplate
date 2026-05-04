<?php

declare(strict_types=1);

use App\Support\UserImageStorageUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_images')) {
            return;
        }

        Schema::table('user_images', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_images', 'image_storage_path')) {
                $table->string('image_storage_path', 2048)->nullable()->after('image_url');
            }
        });

        $this->backfillRelativePathsToAbsoluteUrls();
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_images')) {
            return;
        }

        Schema::table('user_images', function (Blueprint $table): void {
            if (Schema::hasColumn('user_images', 'image_storage_path')) {
                $table->dropColumn('image_storage_path');
            }
        });
    }

    /**
     * Rows that store disk-relative keys (no scheme) get image_storage_path = MD key and absolute URLs for display.
     */
    private function backfillRelativePathsToAbsoluteUrls(): void
    {
        $rows = DB::table('user_images')
            ->select(['id', 'image_url', 'thumbnail_url', 'icon_url'])
            ->get();

        foreach ($rows as $row) {
            $md = $row->image_url;
            if (!is_string($md) || $md === '') {
                continue;
            }

            if (str_contains($md, '://')) {
                continue;
            }

            $updates = [
                'image_storage_path' => $md,
                'image_url' => UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($md) ?? $md),
            ];

            if (
                is_string($row->thumbnail_url) &&
                $row->thumbnail_url !== '' &&
                !str_contains($row->thumbnail_url, '://')
            ) {
                $updates['thumbnail_url'] = UserImageStorageUrl::toAbsoluteHttpUrl(
                    UserImageStorageUrl::publicUrl($row->thumbnail_url) ?? $row->thumbnail_url
                );
            }

            if (is_string($row->icon_url) && $row->icon_url !== '' && !str_contains($row->icon_url, '://')) {
                $updates['icon_url'] = UserImageStorageUrl::toAbsoluteHttpUrl(
                    UserImageStorageUrl::publicUrl($row->icon_url) ?? $row->icon_url
                );
            }

            DB::table('user_images')->where('id', $row->id)->update($updates);
        }
    }
};
