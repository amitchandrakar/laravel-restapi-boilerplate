# Admin SEO Settings API

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
    "gaEnabled": true,
    "gaTrackingCode": "<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX\"></script>",
    "robotsEnabled": true,
    "robotsTxtContent": "User-agent: *\nDisallow:",
    "sitemapEnabled": true,
    "sitemapUrls": ["/", "/browse", "/login", "/register", "/about", "/contact", "/privacy", "/terms"],
    "ogImage": "/og-image.jpg",
    "ogType": "website",
    "twitterCard": "summary_large_image",
    "twitterTitle": "Kurmi Samaj Matrimonial",
    "twitterDescription": "Find your perfect life partner.",
    "twitterImage": "/og-image.jpg"
}
```

All fields are optional; only supplied keys are updated.

## Response

Returns normalized SEO settings object in `data`.

## Test command

```bash
php artisan test tests/Feature/AdminSeoSettingsTest.php
```
