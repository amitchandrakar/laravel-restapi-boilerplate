# Admin Site Settings API

Manage site branding, community restrictions, and basic operational flags.

## Endpoints

- `GET /api/v1/admin/settings/site` (permission: `admin.settings.site.view`)
- `PUT /api/v1/admin/settings/site` (permission: `admin.settings.site.edit`)

Auth for all endpoints: `Bearer` Sanctum token required.

## PUT request body

```json
{
    "siteName": "Kurmi Samaj",
    "logoUrl": "/logo.png",
    "faviconUrl": "/favicon.png",
    "allowedCommunitySurnames": [
        "Chandrakar",
        "Verma",
        "Bais",
        "Kashyap",
        "Patanwar",
        "Chandrawanshi",
        "Kaushik",
        "Deshmukh",
        "Chauhan"
    ],
    "maintenanceMode": false,
    "requireProfileApproval": true
}
```

All fields are optional; only supplied keys are updated.

## Test command

```bash
php artisan test tests/Feature/AdminSiteSettingsTest.php
```
