<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class StorageSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'storage_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'use_path_style_endpoint' => 'boolean',
            'secret_access_key' => 'encrypted',
        ];
    }
}
