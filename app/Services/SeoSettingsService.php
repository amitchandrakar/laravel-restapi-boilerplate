<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\SeoGlobalSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class SeoSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return SeoGlobalSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Seo;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'siteTitle' => 'site_title',
            'defaultDescription' => 'default_description',
            'defaultKeywords' => 'default_keywords',
            'canonicalBaseUrl' => 'canonical_base_url',
            'googleAnalyticsEnabled' => 'google_analytics_enabled',
            'googleAnalyticsSnippet' => 'google_analytics_snippet',
            'robotsEnabled' => 'robots_enabled',
            'robotsTxt' => 'robots_txt',
            'sitemapEnabled' => 'sitemap_enabled',
            'sitemapUrls' => 'sitemap_urls',
            'ogImage' => 'og_image',
            'ogType' => 'og_type',
            'twitterCard' => 'twitter_card',
            'twitterTitle' => 'twitter_title',
            'twitterDescription' => 'twitter_description',
            'twitterImage' => 'twitter_image',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $_maskSecrets): array
    {
        /** @var SeoGlobalSetting $record */
        return [
            'siteTitle' => $record->site_title,
            'defaultDescription' => $record->default_description,
            'defaultKeywords' => $record->default_keywords,
            'canonicalBaseUrl' => $record->canonical_base_url,
            'googleAnalyticsEnabled' => $record->google_analytics_enabled,
            'googleAnalyticsSnippet' => $record->google_analytics_snippet ?? '',
            'robotsEnabled' => $record->robots_enabled,
            'robotsTxt' => $record->robots_txt ?? '',
            'sitemapEnabled' => $record->sitemap_enabled,
            'sitemapUrls' => $record->sitemap_urls ?? '',
            'ogImage' => $record->og_image,
            'ogType' => $record->og_type ?? 'website',
            'twitterCard' => $record->twitter_card ?? 'summary_large_image',
            'twitterTitle' => $record->twitter_title,
            'twitterDescription' => $record->twitter_description,
            'twitterImage' => $record->twitter_image,
        ];
    }
}
