# Postman API collections

Generated Postman artifacts for the Community Connect API. Regenerate after route or request-contract changes — do not edit JSON by hand.

## Quick start

1. Import **Environment**: [`Community-Connect-API.postman_environment.json`](Community-Connect-API.postman_environment.json)
2. Import **Master collection** (recommended): [`Community-Connect-API.postman_collection.json`](Community-Connect-API.postman_collection.json)
   Or import individual module files under [`admin/`](admin/) and [`app/`](app/).
3. Re-import the environment after `php artisan postman:generate` so `BASE_URL` reflects your `.env` `APP_URL` (origin only, no path suffix).
4. Login credentials are pre-filled to match demo seeders: `ADMIN_*` for admin login, `CANDIDATE_*` for app login (override via `POSTMAN_*` in `.env` before generate).
5. Run **Admin → Auth → POST auth/login** or **App → Auth → POST auth/login** — bodies use `{{ADMIN_USERNAME}}` / `{{CANDIDATE_USERNAME}}` and matching passwords.
6. On success, the test script saves `data.token` into `AUTH_TOKEN` automatically (also `session_token_hash` and `candidate_uuid` when returned).
7. Protected requests use **Bearer Token** auth with `{{AUTH_TOKEN}}`.

## Regenerate

```bash
php artisan postman:generate
```

Optional output directory:

```bash
php artisan postman:generate --output=docs/postman
```

## Layout

| Path                                              | Description                                              |
| ------------------------------------------------- | -------------------------------------------------------- |
| `Community-Connect-API.postman_collection.json`   | Master collection (`Admin/*`, `App/*`, `Infrastructure`) |
| `Community-Connect-API.postman_environment.json`  | Shared variables                                         |
| `admin/{module}/{module}.postman_collection.json` | Admin module collections                                 |
| `app/{module}/{module}.postman_collection.json`   | App / member module collections                          |

### Modules (124 requests)

**Admin:** auth, candidates, dashboard, packages, subscriptions, payments, reports, settings, team-users, users

**App:** auth, candidate-kyc, candidate-profile, contact-requests, discovery, me, public, webhooks

**Infrastructure** (master only): `GET /api`, `GET /api/health`, `GET /api/health/detailed`

## Authentication

| Realm                      | Token variable   | Notes                                                   |
| -------------------------- | ---------------- | ------------------------------------------------------- |
| Admin                      | `{{AUTH_TOKEN}}` | Sanctum Bearer; requires admin role/permissions         |
| App                        | `{{AUTH_TOKEN}}` | Sanctum Bearer; candidate/member routes                 |
| Public / webhooks / health | none (noauth)    | Login, register, featured candidates, Razorpay webhooks |

### Tracked session

Routes using `tracked.session` require a valid login Bearer token tied to an active server-side session. If the session expires, responses use `403` with `error.code` `SESSION_INVALID`.

### Admin permissions

Many admin routes include `permission:admin.*` middleware. The admin user must have the matching Spatie permission or the API returns `403` `FORBIDDEN`.

### Me routes

`/api/v1/app/me/*` routes may send optional header `X-User-Profile-Uuid: {{candidate_uuid}}`. When sent, it must match the authenticated user’s UUID.

## Example responses

Each request includes saved examples for:

- Success (200/201)
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden (or `SESSION_INVALID` on tracked-session routes)
- 404 Not Found
- 422 Validation Error
- 429 Too Many Requests
- 500 Internal Server Error

Bodies follow [`ApiResponseBuilder`](../../app/Support/ApiResponseBuilder.php): `{ success, statusCode, message, data, error, meta }`.

## Environment variables

| Variable                     | Usage                                                          |
| ---------------------------- | -------------------------------------------------------------- |
| `BASE_URL`                   | API origin from `APP_URL` at generate time (no trailing slash) |
| `ADMIN_USERNAME`             | Admin login (`admin@example.com` by default)                   |
| `ADMIN_PASSWORD`             | Admin login (`1234567890` by default)                          |
| `CANDIDATE_USERNAME`         | App login (`candidate.parichay@example.com` by default)        |
| `CANDIDATE_PASSWORD`         | App login (`1234567890` by default)                            |
| `AUTH_TOKEN`                 | Bearer token (set automatically after login/register/refresh)  |
| `session_token_hash`         | From login response (set automatically when present)           |
| `candidate_uuid`             | Path param `{user}`, `{candidate}`, profile header             |
| `package_uuid`               | Packages / registration                                        |
| `payment_uuid`               | Payments                                                       |
| `import_batch_id`            | CSV import status                                              |
| `document_uuid`              | KYC documents                                                  |
| `role_uuid`                  | Admin roles                                                    |
| `notification_id`            | Member notifications                                           |
| `image_uuid`                 | Profile photos                                                 |
| `contact_request_uuid`       | Contact requests                                               |
| `razorpay_webhook_signature` | `X-Razorpay-Signature` for webhooks                            |

## Candidate profile (app routes)

Profile section saves and full profile read/write live under **app**, not admin:

- `GET/PUT /api/v1/app/auth/candidate/profile/details`
- `PATCH /api/v1/app/auth/candidate/profile/{section}`
- `GET /api/v1/app/auth/candidate/{uuid}/profile-details` (peer view)

See [`app/candidate-profile/`](app/candidate-profile/) in this folder.

## Implementation

Generator source: `app/Support/Postman/*`, command `app/Console/Commands/GeneratePostmanCollection.php`.

Routes are discovered from Laravel’s router; request bodies are inferred from Form Request `rules()`; controllers are reflected for Form Request types.
