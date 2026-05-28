<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class SocialLoginSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'social_login_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'google_enabled' => 'boolean',
            'facebook_enabled' => 'boolean',
            'instagram_enabled' => 'boolean',
            'google_live_client_secret' => 'encrypted',
            'facebook_live_client_secret' => 'encrypted',
            'instagram_live_client_secret' => 'encrypted',
        ];
    }
}
