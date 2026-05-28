<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateSiteSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'siteName' => ['sometimes', 'required', 'string', 'max:255'],
            'logoUrl' => ['sometimes', 'nullable', 'string', 'max:500000'],
            'faviconUrl' => ['sometimes', 'nullable', 'string', 'max:500000'],
            'contactEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contactPhone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'contactAddress' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'allowedCommunitySurnames' => ['sometimes', 'array'],
            'allowedCommunitySurnames.*' => ['string', 'max:128'],
            'maintenanceMode' => ['sometimes', 'boolean'],
            'requireProfileApproval' => ['sometimes', 'boolean'],
            'successStoriesCount' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
