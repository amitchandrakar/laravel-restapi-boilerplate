<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoSettingsService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        $map = [
            'siteTitle' => 'site_title',
            'defaultDescription' => 'default_description',
            'defaultKeywords' => 'default_keywords',
            'canonicalBaseUrl' => 'canonical_base_url',
            'gaEnabled' => 'ga_enabled',
            'gaTrackingCode' => 'ga_tracking_code',
            'robotsEnabled' => 'robots_enabled',
            'robotsTxtContent' => 'robots_txt_content',
            'sitemapEnabled' => 'sitemap_enabled',
            'sitemapUrls' => 'sitemap_urls',
            'ogImage' => 'og_image',
            'ogType' => 'og_type',
            'twitterCard' => 'twitter_card',
            'twitterTitle' => 'twitter_title',
            'twitterDescription' => 'twitter_description',
            'twitterImage' => 'twitter_image',
        ];

        DB::transaction(function () use ($data, $map): void {
            foreach ($map as $payloadKey => $settingKey) {
                if (!array_key_exists($payloadKey, $data)) {
                    continue;
                }

                $value = $data[$payloadKey];
                $valueType = 'string';

                if (is_bool($value)) {
                    $valueType = 'boolean';
                    $value = $value ? '1' : '0';
                } elseif (is_array($value)) {
                    $valueType = 'json';
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                } elseif ($value !== null && (is_int($value) || is_float($value))) {
                    $valueType = 'number';
                    $value = (string) $value;
                } elseif ($value === null) {
                    $valueType = 'string';
                } else {
                    $value = (string) $value;
                }

                DB::table('settings')->updateOrInsert(
                    ['group_key' => 'seo', 'setting_key' => $settingKey],
                    [
                        'uuid' => (string) Str::uuid(),
                        'setting_value' => $value,
                        'value_type' => $valueType,
                        'is_public' => false,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return $this->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $rows = DB::table('settings')->where('group_key', 'seo')->get();
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(string) $row->setting_key] = $this->castSettingValue(
                $row->setting_value,
                (string) $row->value_type
            );
        }

        return [
            'siteTitle' => $indexed['site_title'] ?? null,
            'defaultDescription' => $indexed['default_description'] ?? null,
            'defaultKeywords' => $indexed['default_keywords'] ?? null,
            'canonicalBaseUrl' => $indexed['canonical_base_url'] ?? null,
            'gaEnabled' => (bool) ($indexed['ga_enabled'] ?? false),
            'gaTrackingCode' => $indexed['ga_tracking_code'] ?? null,
            'robotsEnabled' => (bool) ($indexed['robots_enabled'] ?? false),
            'robotsTxtContent' => $indexed['robots_txt_content'] ?? null,
            'sitemapEnabled' => (bool) ($indexed['sitemap_enabled'] ?? false),
            'sitemapUrls' => is_array($indexed['sitemap_urls'] ?? null) ? $indexed['sitemap_urls'] : [],
            'ogImage' => $indexed['og_image'] ?? null,
            'ogType' => $indexed['og_type'] ?? 'website',
            'twitterCard' => $indexed['twitter_card'] ?? 'summary_large_image',
            'twitterTitle' => $indexed['twitter_title'] ?? null,
            'twitterDescription' => $indexed['twitter_description'] ?? null,
            'twitterImage' => $indexed['twitter_image'] ?? null,
        ];
    }

    private function castSettingValue(mixed $value, string $valueType): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($valueType) {
            'boolean' => (string) $value === '1',
            'number' => is_numeric($value) ? $value + 0 : $value,
            'json' => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
