# Phase 1 — app-plan.md Partial Alignment Tracker

> **Scope:** Quick wins from partial alignment (excludes **2FA** and **cursor pagination** per project decision).
> **Started:** 2026-05-20

## Status legend

| Symbol | Meaning            |
| ------ | ------------------ |
| ✅     | Done               |
| ⏳     | In progress        |
| ⬜     | Not started        |
| 🚫     | Explicitly skipped |

## Project exclusions (documented)

| Item              | Decision                                                                         |
| ----------------- | -------------------------------------------------------------------------------- |
| 2FA               | Not required — not implemented                                                   |
| Cursor pagination | Not required — keep offset (`page` / `limit`) for admin + mobile infinite scroll |

---

## Phase 1 batch — implementation log

| #   | Task                                                                                        | Status | Files / notes                                                                            |
| --- | ------------------------------------------------------------------------------------------- | ------ | ---------------------------------------------------------------------------------------- |
| 1   | Unified validation & authorization error envelope (`ApiFormRequest` → `ApiResponseBuilder`) | ✅     | `app/Http/Requests/Api/ApiFormRequest.php`                                               |
| 2   | Sanctum token expiry (30 days default)                                                      | ✅     | `app/Support/SanctumAuthToken.php`, `app/Services/AuthService.php`, `config/api.php`     |
| 3   | Login lockout after N failed attempts (423 Locked)                                          | ✅     | `app/Services/LoginLockoutService.php`, `app/Services/AuthService.php`                   |
| 4   | Stricter rate limits on auth endpoints (5/min)                                              | ✅     | `app/Providers/AppServiceProvider.php`, `routes/api/v1.php`, `config/api.php`            |
| 5   | Razorpay webhook → queued `ProcessPaymentWebhook` job                                       | ✅     | `app/Jobs/ProcessPaymentWebhook.php`, `RegistrationPaymentController` returns **202**    |
| 6   | Job retry config (`$tries`, `$timeout`, `$backoff`, `failed()`)                             | ✅     | `app/Jobs/Concerns/ConfiguresQueueRetries.php`, all `app/Jobs/*`                         |
| 7   | All notifications implement `ShouldQueue`                                                   | ✅     | `app/Notifications/*.php`                                                                |
| 8   | Feature tests for lockout + validation envelope                                             | ✅     | `tests/Feature/AuthLoginLockoutTest.php`, `tests/Feature/ApiFormRequestEnvelopeTest.php` |

---

## Phase 2

See **[PHASE2_APP_PLAN_ALIGNMENT.md](./PHASE2_APP_PLAN_ALIGNMENT.md)** for the Phase 2 log (CI, health, KYC signed URLs, caching, policies).

## Phase 3+ (not started)

| Task                                  | Status |
| ------------------------------------- | ------ |
| PHPStan level 8 ramp                  | ⬜     |
| Redis default queue + priority queues | ⬜     |
| Nightly `RecalculateMatchScores`      | ⬜     |
| OTP / Twilio / Socialite              | ⬜     |
| Algolia / Scout                       | ⬜     |

---

## Config / env additions

```env
API_AUTH_TOKEN_EXPIRY_DAYS=30
API_AUTH_LOCKOUT_MAX_ATTEMPTS=5
API_AUTH_LOCKOUT_DECAY_MINUTES=15
API_AUTH_RATE_LIMIT_PER_MINUTE=5
```

---

## API behavior changes (for client/QA)

1. **Form Request validation errors** now use the standard envelope (`success`, `statusCode`, `error.code`, `meta.requestId`, etc.) instead of `{ message, code, errors }`.
2. **Razorpay webhook** responds with **202 Accepted** after enqueueing processing (was 200 synchronous).
3. **Login lockout:** After 5 failed attempts for the same account, login returns **423** until lockout window expires.
4. **New Sanctum tokens** include `expires_at` (~30 days from issuance).

---

## Implementation notes (2026-05-20)

- Notifications that implement `ShouldQueue` must also use the `Queueable` trait (Laravel expects `$connection` on queued notifications).
- PHPUnit sets `API_AUTH_RATE_LIMIT_PER_MINUTE=120` so multi-step auth feature tests are not blocked by the production 5/min auth throttle.

## Verification

```bash
composer lint
composer analyse
php artisan test
```

**Last run:** `php artisan test` — 168 passed.
