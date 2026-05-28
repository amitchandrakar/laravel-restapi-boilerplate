<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateStorageSettingsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'isEnabled' => ['sometimes', 'boolean'],
            'driver' => ['sometimes', 'string', 'max:32'],
            'bucket' => ['sometimes', 'nullable', 'string', 'max:255'],
            'region' => ['sometimes', 'nullable', 'string', 'max:64'],
            'accessKeyId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'secretAccessKey' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'endpoint' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'usePathStyleEndpoint' => ['sometimes', 'boolean'],
        ];
    }
}
