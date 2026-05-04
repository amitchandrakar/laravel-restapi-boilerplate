<?php

declare(strict_types=1);

use App\Support\UserImageStorageUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_images')) {
            return;
        }

        $rows = DB::table('user_images')
            ->select(['id', 'image_url', 'thumbnail_url', 'icon_url', 'image_storage_path'])
            ->get();

        foreach ($rows as $row) {
            $updates = [];

            foreach (['image_url', 'thumbnail_url', 'icon_url'] as $column) {
                $value = $row->{$column};
                if (!is_string($value) || $value === '' || str_contains($value, '://')) {
                    continue;
                }
                $updates[$column] = UserImageStorageUrl::toAbsoluteHttpUrl(
                    UserImageStorageUrl::publicUrl($value) ?? $value
                );
            }

            $img = $row->image_url;
            if (
                is_string($img) &&
                $img !== '' &&
                !str_contains($img, '://') &&
                (empty($row->image_storage_path) || $row->image_storage_path === null)
            ) {
                $updates['image_storage_path'] = $img;
            }

            if ($updates !== []) {
                DB::table('user_images')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Irreversible URL normalization.
    }
};
