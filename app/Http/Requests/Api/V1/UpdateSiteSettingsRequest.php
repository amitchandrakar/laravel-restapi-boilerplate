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
            'logoUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'faviconUrl' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'allowedCommunitySurnames' => ['sometimes', 'array'],
            'allowedCommunitySurnames.*' => ['string', 'max:128'],
            'maintenanceMode' => ['sometimes', 'boolean'],
            'requireProfileApproval' => ['sometimes', 'boolean'],
        ];
    }
}
