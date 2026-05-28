<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\PaymentGatewaySetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return PaymentGatewaySetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::PaymentGateway;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'gateway' => 'gateway',
            'isEnabled' => 'is_enabled',
            'environment' => 'environment',
            'liveKeyId' => 'live_key_id',
            'liveKeySecret' => 'live_key_secret',
            'sandboxKeyId' => 'sandbox_key_id',
            'sandboxKeySecret' => 'sandbox_key_secret',
            'webhookSecret' => 'webhook_secret',
            'currency' => 'currency',
            'checkoutOptionsJson' => 'checkout_options_json',
            'webhookUrl' => 'webhook_url',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['live_key_secret', 'sandbox_key_secret', 'webhook_secret'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var PaymentGatewaySetting $record */
        return array_merge(
            [
                'gateway' => $record->gateway ?? 'razorpay',
                'isEnabled' => $record->is_enabled,
                'environment' => $record->environment ?? 'sandbox',
                'liveKeyId' => $record->live_key_id,
                'sandboxKeyId' => $record->sandbox_key_id,
                'currency' => $record->currency ?? 'INR',
                'checkoutOptionsJson' => $record->checkout_options_json ?? '',
                'webhookUrl' => $record->webhook_url,
            ],
            $maskSecrets
                ? $this->secretFlags($record)
                : [
                    'liveKeySecret' => $record->live_key_secret,
                    'sandboxKeySecret' => $record->sandbox_key_secret,
                    'webhookSecret' => $record->webhook_secret,
                ]
        );
    }
}
