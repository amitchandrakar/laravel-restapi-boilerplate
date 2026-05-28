<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class SiteSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'site_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_community_surnames' => 'array',
            'maintenance_mode' => 'boolean',
            'require_profile_approval' => 'boolean',
            'success_stories_count' => 'integer',
        ];
    }
}
