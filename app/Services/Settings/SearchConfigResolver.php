<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\SearchSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class SearchConfigResolver
{
    public function apply(): void
    {
        $settings = SearchSetting::instance();

        if (!$settings->is_enabled) {
            return;
        }

        if (filled($settings->app_id)) {
            Config::set('scout.algolia.id', $settings->app_id);
        }

        if (filled($settings->admin_api_key)) {
            Config::set('scout.algolia.secret', $settings->admin_api_key);
        }

        if (filled($settings->candidate_index_name)) {
            Config::set('scout.prefix', '');
            Config::set('scout.indexes.' . User::class, $settings->candidate_index_name);
        }
    }
}
