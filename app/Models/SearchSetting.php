<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class SearchSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'search_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'queue_indexing' => 'boolean',
            'admin_api_key' => 'encrypted',
            'search_api_key' => 'encrypted',
        ];
    }
}
