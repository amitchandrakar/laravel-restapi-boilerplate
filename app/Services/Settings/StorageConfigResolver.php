<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Config;

class StorageConfigResolver
{
    public function apply(): void
    {
        $settings = StorageSetting::instance();

        if (!$settings->is_enabled) {
            return;
        }

        if (!filled($settings->bucket) || !filled($settings->access_key_id) || !filled($settings->secret_access_key)) {
            return;
        }

        Config::set('filesystems.disks.s3.key', $settings->access_key_id);
        Config::set('filesystems.disks.s3.secret', $settings->secret_access_key);
        Config::set('filesystems.disks.s3.region', $settings->region);
        Config::set('filesystems.disks.s3.bucket', $settings->bucket);

        if (filled($settings->endpoint)) {
            Config::set('filesystems.disks.s3.endpoint', $settings->endpoint);
        }

        if (filled($settings->url)) {
            Config::set('filesystems.disks.s3.url', $settings->url);
        }

        Config::set('filesystems.disks.s3.use_path_style_endpoint', $settings->use_path_style_endpoint);
    }
}
