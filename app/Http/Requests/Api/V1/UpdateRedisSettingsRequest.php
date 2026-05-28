<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateRedisSettingsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'isEnabled' => ['sometimes', 'boolean'],
            'client' => ['sometimes', 'string', 'max:32'],
            'host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'nullable', 'string', 'max:128'],
            'password' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'database' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'useTls' => ['sometimes', 'boolean'],
            'cachePrefix' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }
}
