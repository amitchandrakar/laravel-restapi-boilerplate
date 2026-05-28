<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateLegalPageRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'version' => ['sometimes', 'nullable', 'string', 'max:32'],
            'isPublished' => ['sometimes', 'boolean'],
            'publishedAt' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
