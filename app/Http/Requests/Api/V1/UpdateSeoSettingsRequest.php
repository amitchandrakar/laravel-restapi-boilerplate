<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateSeoSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'siteTitle' => ['sometimes', 'required', 'string', 'max:255'],
            'defaultDescription' => ['sometimes', 'required', 'string', 'max:2000'],
            'defaultKeywords' => ['sometimes', 'required', 'string', 'max:2000'],
            'canonicalBaseUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],

            'gaEnabled' => ['sometimes', 'boolean'],
            'gaTrackingCode' => ['sometimes', 'nullable', 'string', 'max:20000'],

            'robotsEnabled' => ['sometimes', 'boolean'],
            'robotsTxtContent' => ['sometimes', 'nullable', 'string', 'max:20000'],

            'sitemapEnabled' => ['sometimes', 'boolean'],
            'sitemapUrls' => ['sometimes', 'array'],
            'sitemapUrls.*' => ['string', 'max:512'],

            'ogImage' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'ogType' => ['sometimes', 'nullable', 'string', Rule::in(['website', 'article', 'profile'])],

            'twitterCard' => ['sometimes', 'nullable', 'string', Rule::in(['summary', 'summary_large_image'])],
            'twitterTitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'twitterDescription' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'twitterImage' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
