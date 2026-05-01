<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateSocialLoginSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'googleEnabled' => ['sometimes', 'boolean'],
            'googleEnvironment' => ['sometimes', 'string', Rule::in(['sandbox', 'live'])],
            'googleLiveClientId' => ['sometimes', 'nullable', 'string', 'max:512'],
            'googleLiveClientSecret' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'googleLiveRedirectUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],

            'facebookEnabled' => ['sometimes', 'boolean'],
            'facebookEnvironment' => ['sometimes', 'string', Rule::in(['sandbox', 'live'])],
            'facebookLiveClientId' => ['sometimes', 'nullable', 'string', 'max:512'],
            'facebookLiveClientSecret' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'facebookLiveRedirectUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],

            'instagramEnabled' => ['sometimes', 'boolean'],
            'instagramEnvironment' => ['sometimes', 'string', Rule::in(['sandbox', 'live'])],
            'instagramLiveClientId' => ['sometimes', 'nullable', 'string', 'max:512'],
            'instagramLiveClientSecret' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'instagramLiveRedirectUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }
}
