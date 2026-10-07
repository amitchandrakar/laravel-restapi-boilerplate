# Cursor knowledge index

Thin map for AI agents. Product detail lives in the linked docs. When a doc and the code disagree, trust **routes, Feature tests, and services**.

## Stack

- PHP 8.2, Laravel 12, Sanctum, Spatie permissions
- Pest 4, Laravel Pint, PHPCS (Slevomat), PHPStan level 8 (Larastan)
- Scout + Algolia for published candidate search, with a MySQL browse fallback
- Firebase Cloud Messaging HTTP v1 for member push
- Quality: `composer format`, `composer phpcs`, `composer analyse`, `composer test`, `composer quality`
- CI: `.github/workflows/ci.yml`. Local git hook: `git-hooks/pre-commit`

## Layout

```
routes/api.php
  routes/api/v1.php          auth | admin | app
    routes/api/v1/auth.php
    routes/api/v1/admin.php
    routes/api/v1/app.php    mounts public.php; member me + auth groups
    routes/api/v1/public.php unauthenticated /api/v1/app/public/*
app/Http/Controllers         thin
app/Services                 business logic
app/Support/ApiResponseBuilder.php
tests/Feature                Pest API tests
```

Controllers stay thin. Put behavior in services. Public IDs in URLs are UUIDs (`{candidate:uuid}`), not numeric primary keys.

API JSON uses `ApiResponseBuilder` / the `ApiResponse` trait: `success`, `statusCode`, `message`, `data`, `error`, `meta`.

Member routes use `auth:sanctum` and `tracked.session`. `/api/v1/app/me/*` also uses `profile.uuid.header` (`X-User-Profile-Uuid`). Do not remove those middleware. A mismatched profile UUID returns 403 with the existing body; do not “normalize” it without updating clients.

Guest browse and profile detail (`public.php`) return teaser fields only. Do not call the admin profile builder with a null viewer.

Schema changes are new migration files. Do not edit `database/migrations/2026_05_20_000000_create_community_connect_schema.php`.

`docs/postman/**/*.json` is generated. After route or FormRequest changes run `php artisan postman:generate`. Do not hand-edit those JSON files.

Tests use `SCOUT_DRIVER=collection` (`phpunit.xml`). Do not require a live Algolia index in Pest.

## Implemented vs missing

| Area | Status | Where to read |
|------|--------|----------------|
| Shared auth, tracked sessions | Implemented | [shared_auth_api.md](shared_auth_api.md) |
| Admin RBAC | Implemented | [module_role_permission.md](module_role_permission.md) |
| Package entitlements | Implemented | [package_feature_permissions.md](package_feature_permissions.md) |
| Member onboarding, KYC, FCM devices | Implemented | [member_onboarding_me_api.md](member_onboarding_me_api.md) |
| Discovery, favorites, matches | Implemented | routes + `tests/Feature/CandidateDiscoveryApiTest.php` |
| Public CMS, guest browse/detail | Implemented | [app_public_cms_api.md](app_public_cms_api.md) |
| Algolia + DB browse | Implemented | `CandidateBrowseService`, `config/scout.php` |
| Notifications feed + FCM send | Implemented | [member_notifications_api.md](member_notifications_api.md), `FcmPushService` |
| Admin settings (site, search, storage, payments config) | Implemented | matching `docs/admin_*_api.md` |
| OTP / Twilio login | Not implemented | |
| Socialite login | Not implemented (admin can store social credentials only) | |
| Razorpay order create + webhook | Not implemented (settings row exists; checkout throws when payment is required) | |

## Doc trust

Prefer, in order:

1. `routes/api/v1/*.php` and the controller/service they call
2. `tests/Feature/*`
3. Docs that match those routes

Do **not** implement features only because these files mention them:

- `app-plan.md` — product wishlist (OTP, Socialite, live Razorpay)
- `docs/PHASE1_APP_PLAN_ALIGNMENT.md` and `docs/PHASE2_APP_PLAN_ALIGNMENT.md` — Algolia marked undone; code and PHASE3 disagree
- `docs/notifications_plan.md` — gap list is stale; match and profile-view notifications exist
- `README.md` / `CHANGELOG.md` — still describe an older Laravel boilerplate

## Secrets

Never commit `.env`, Firebase service account JSON, or files under `storage/app/firebase/` except `.gitkeep`.
