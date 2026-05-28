<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class RedisSetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'redis_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'use_tls' => 'boolean',
            'port' => 'integer',
            'database' => 'integer',
            'password' => 'encrypted',
        ];
    }
}
