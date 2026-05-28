<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentGatewaySettingsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gateway' => ['sometimes', 'string', 'max:64'],
            'isEnabled' => ['sometimes', 'boolean'],
            'environment' => ['sometimes', Rule::in(['sandbox', 'live'])],
            'liveKeyId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'liveKeySecret' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'sandboxKeyId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sandboxKeySecret' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'webhookSecret' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'currency' => ['sometimes', 'string', 'max:8'],
            'checkoutOptionsJson' => ['sometimes', 'nullable', 'string', 'json', 'max:50000'],
            'webhookUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
