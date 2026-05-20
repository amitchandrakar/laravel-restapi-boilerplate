# Phase 2 — app-plan.md Partial Alignment Tracker

> Continues from [PHASE1_APP_PLAN_ALIGNMENT.md](./PHASE1_APP_PLAN_ALIGNMENT.md).
> Next: [PHASE3_APP_PLAN_ALIGNMENT.md](./PHASE3_APP_PLAN_ALIGNMENT.md) (PHPStan 8, Redis queues, Algolia).
> **Exclusions unchanged:** no 2FA, no cursor pagination.

## Phase 2 batch — implementation log

| #   | Task                                                     | Status | Files / notes                                                                                                       |
| --- | -------------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------- |
| 1   | GitHub Actions CI (Pint, PHPStan, tests, composer audit) | ✅     | `.github/workflows/ci.yml`                                                                                          |
| 2   | PHPStan stub fix + CI analyse step                       | ✅     | `stubs/razorpay-api.stub` created; **level 6** trial = 141 issues — CI stays at **level 5** until baseline expanded |
| 3   | Health checks (DB, cache, queue, storage, S3 probe)      | ✅     | `HealthCheckService`, `/api/health`, `/api/health/detailed`, `GET /api/v1/admin/system-health`                      |
| 4   | KYC signed URLs (S3 private + temporary URLs)            | ✅     | `UserImageStorageUrl::resolveKycDocumentUrl`, `config/kyc_id_verification.php`                                      |
| 5   | Caching strategy (TTL config + key convention)           | ✅     | `config/cache_strategy.php`, `CacheKeys`, dashboard / profile options / featured lists                              |
| 6   | Policies (user profile, KYC, contact requests)           | ✅     | `app/Policies/*`, Form Request `authorize()`                                                                        |

## Phase 3+ (not in this batch)

| Task                                                          | Status |
| ------------------------------------------------------------- | ------ |
| PHPStan level **8**                                           | ⬜     |
| Redis default queue + priority queues                         | ⬜     |
| Nightly `RecalculateMatchScores`                              | ⬜     |
| Cache invalidation observers (permissions, master data seeds) | ⬜     |
| OTP / Twilio / Socialite                                      | ⬜     |
| Algolia / Scout                                               | ⬜     |

## New / updated endpoints

| Method | Path                          | Auth                           |
| ------ | ----------------------------- | ------------------------------ |
| GET    | `/api/health`                 | Public — lightweight           |
| GET    | `/api/health/detailed`        | Public — full service matrix   |
| GET    | `/api/v1/admin/system-health` | Admin (`admin.dashboard.view`) |

## Env additions

```env
CACHE_TTL_DASHBOARD_METRICS=900
CACHE_TTL_PROFILE_OPTIONS=3600
CACHE_TTL_FEATURED_PROFILES=300
KYC_USE_SIGNED_URLS=false
KYC_SIGNED_URL_MINUTES=15
```

## Verification

```bash
composer lint
composer analyse
php artisan test
```
