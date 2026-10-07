<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsSingletonSetting;

/**
 * @property string $uuid
 * @property string $gateway
 * @property bool $is_enabled
 * @property string $environment
 * @property string|null $live_key_id
 * @property string|null $sandbox_key_id
 * @property string|null $live_key_secret
 * @property string|null $sandbox_key_secret
 * @property string|null $webhook_secret
 * @property string $currency
 * @property array<string, mixed>|null $checkout_options_json
 * @property string|null $webhook_url
 */
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
            'checkout_options_json' => 'array',
            'live_key_secret' => 'encrypted',
            'sandbox_key_secret' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }
}
