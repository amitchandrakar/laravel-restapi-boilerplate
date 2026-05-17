<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidatePreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'phoneAlertsEnabled' => ['sometimes', 'boolean'],
            'emailNotificationsEnabled' => ['sometimes', 'boolean'],
            'showOnlineStatus' => ['sometimes', 'boolean'],
            'hidePhoneNumber' => ['sometimes', 'boolean'],
        ];
    }
}
