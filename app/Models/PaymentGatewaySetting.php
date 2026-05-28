<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

class PaymentGatewaySetting extends BaseModel
{
    use IsSingletonSetting;

    protected $table = 'payment_gateway_settings';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'live_key_secret' => 'encrypted',
            'sandbox_key_secret' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }
}
