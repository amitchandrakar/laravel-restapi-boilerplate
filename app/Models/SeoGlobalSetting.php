<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class SeoGlobalSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'seo_global_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'google_analytics_enabled' => 'boolean',
            'robots_enabled' => 'boolean',
            'sitemap_enabled' => 'boolean',
        ];
    }
}
