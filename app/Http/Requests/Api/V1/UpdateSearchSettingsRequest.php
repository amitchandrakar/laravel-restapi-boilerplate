<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateSearchSettingsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'isEnabled' => ['sometimes', 'boolean'],
            'driver' => ['sometimes', 'string', 'max:64'],
            'appId' => ['sometimes', 'nullable', 'string', 'max:128'],
            'adminApiKey' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'searchApiKey' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'candidateIndexName' => ['sometimes', 'nullable', 'string', 'max:128'],
            'queueIndexing' => ['sometimes', 'boolean'],
        ];
    }
}
