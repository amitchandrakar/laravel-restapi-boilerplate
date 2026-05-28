# Admin SEO Settings API

Global SEO is stored in `seo_global_settings` (singleton). Per-page rows remain in `seo_settings` for future use.

Manage global SEO defaults and related metadata used by web/app rendering.

## Endpoints

- `GET /api/v1/admin/settings/seo` (permission: `admin.settings.seo.view`)
- `PUT /api/v1/admin/settings/seo` (permission: `admin.settings.seo.edit`)

Auth for all endpoints: `Bearer` Sanctum token required.

## PUT request body

```json
{
    "siteTitle": "Kurmi Samaj Matrimonial",
    "defaultDescription": "Kurmi Samaj Matrimonial – Find your perfect life partner from the Kurmi community.",
    "defaultKeywords": "Kurmi matrimony, Kurmi Samaj, Kurmi marriage, Kurmi shaadi",
    "canonicalBaseUrl": "https://example.com",
    "googleAnalyticsEnabled": true,
    "googleAnalyticsSnippet": "<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX\"></script>",
    "robotsEnabled": true,
    "robotsTxt": "User-agent: *\nDisallow:",
    "sitemapEnabled": true,
    "sitemapUrls": "/\n/browse\n/login\n/register\n/about\n/contact\n/privacy\n/terms",
    "ogImage": "/og-image.jpg",
    "ogType": "website",
    "twitterCard": "summary_large_image",
    "twitterTitle": "Kurmi Samaj Matrimonial",
    "twitterDescription": "Find your perfect life partner.",
    "twitterImage": "/og-image.jpg"
}
```

All fields are optional; only supplied keys are updated.

`sitemapUrls` is a newline-separated list of paths or full URLs (matches the admin UI textarea).

## Response

Returns normalized SEO settings object in `data`.

## Test command

```bash
php artisan test tests/Feature/AdminSeoSettingsTest.php
```
