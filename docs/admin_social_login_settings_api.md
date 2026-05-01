# Admin Social Login Settings API

Manage social login provider settings for Google, Facebook, and Instagram.

## Endpoints

- `GET /api/v1/admin/settings/social-login` (permission: `admin.settings.social.view`)
- `PUT /api/v1/admin/settings/social-login` (permission: `admin.settings.social.edit`)

Auth for all endpoints: `Bearer` Sanctum token required.

## PUT request body

```json
{
    "googleEnabled": true,
    "googleEnvironment": "live",
    "googleLiveClientId": "google-client-id",
    "googleLiveClientSecret": "google-client-secret",
    "googleLiveRedirectUrl": "https://example.com/auth/google/callback",
    "facebookEnabled": true,
    "facebookEnvironment": "live",
    "facebookLiveClientId": "facebook-client-id",
    "facebookLiveClientSecret": "facebook-client-secret",
    "facebookLiveRedirectUrl": "https://example.com/auth/facebook/callback",
    "instagramEnabled": true,
    "instagramEnvironment": "live",
    "instagramLiveClientId": "instagram-client-id",
    "instagramLiveClientSecret": "instagram-client-secret",
    "instagramLiveRedirectUrl": "https://example.com/auth/instagram/callback"
}
```

All fields are optional; only supplied keys are updated.

## Test command

```bash
php artisan test tests/Feature/AdminSocialLoginSettingsTest.php
```
