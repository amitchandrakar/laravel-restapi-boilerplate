<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Config;

class PaymentGatewayConfigResolver
{
    public function apply(): void
    {
        $settings = PaymentGatewaySetting::instance();

        if (!$settings->is_enabled) {
            return;
        }

        $isLive = $settings->environment === 'live';
        $keyId = $isLive ? $settings->live_key_id : $settings->sandbox_key_id;
        $keySecret = $isLive ? $settings->live_key_secret : $settings->sandbox_key_secret;

        if (!filled($keyId) || !filled($keySecret)) {
            return;
        }

        Config::set('services.razorpay.key_id', $keyId);
        Config::set('services.razorpay.key_secret', $keySecret);

        if (filled($settings->webhook_secret)) {
            Config::set('services.razorpay.webhook_secret', $settings->webhook_secret);
        }

        if (filled($settings->currency)) {
            Config::set('services.razorpay.currency', $settings->currency);
        }

        $checkoutOptions = $this->decodeCheckoutOptions($settings->checkout_options_json);

        if ($checkoutOptions !== []) {
            Config::set('services.razorpay.checkout', $checkoutOptions);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCheckoutOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
