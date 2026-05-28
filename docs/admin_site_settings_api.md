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
    "contactEmail": "support@example.com",
    "contactPhone": "+91-9876543210",
    "contactAddress": "Raipur, Chhattisgarh",
    "maintenanceMode": false,
    "requireProfileApproval": true,
    "successStoriesCount": 120
}
```

All fields are optional; only supplied keys are updated. Values are stored in the `site_settings` singleton table (not EAV `settings`).

## Test command

```bash
php artisan test tests/Feature/AdminSiteSettingsTest.php
```
