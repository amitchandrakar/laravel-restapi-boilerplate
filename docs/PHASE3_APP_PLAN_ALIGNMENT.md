# Phase 3 — App Plan Alignment

Tracks **PHPStan level 8**, **Redis queues with priorities**, and **Algolia (Laravel Scout)** from `app-plan.md` Phase 3.

**Security:** Credentials shared in chat must be rotated. Store Redis/Algolia values only in local `.env` (never commit). Use Algolia **Write** API key as `ALGOLIA_SECRET`.

---

## 1. PHPStan level 6 → 8

| Item                                           | Status                   |
| ---------------------------------------------- | ------------------------ |
| `phpstan.neon` level **8**                     | Done                     |
| Expanded `phpstan-baseline.neon` (~194 issues) | Done                     |
| CI runs `./vendor/bin/phpstan analyse`         | Done (existing workflow) |

---

## 2. Redis queues + priorities

| Item                                                                                                         | Status |
| ------------------------------------------------------------------------------------------------------------ | ------ |
| Default `QUEUE_CONNECTION=redis` in `config/queue.php`                                                       | Done   |
| `config/queue_priorities.php` + `App\Support\QueuePriority`                                                  | Done   |
| `ProcessPaymentWebhook` → **critical**                                                                       | Done   |
| `StartUserSessionJob` → **high**                                                                             | Done   |
| Audit / activity log jobs → **default**                                                                      | Done   |
| Scout sync jobs → **low** (`MakeSearchableOnLowQueue`, `RemoveFromSearchOnLowQueue`, `SyncProfileToAlgolia`) | Done   |
| `.env.example` Redis + queue names                                                                           | Done   |
| PHPUnit: `QUEUE_CONNECTION=sync`                                                                             | Done   |

**Local Redis Cloud (TLS):** set in `.env` only, for example:

```env
REDIS_CLIENT=predis
REDIS_URL=rediss://default:YOUR_PASSWORD@YOUR_HOST:YOUR_PORT/0
QUEUE_CONNECTION=redis
```

Run workers per priority (or one worker listening to all):

```bash
php artisan queue:work redis --queue=critical,high,default,low
```

---

## 3. Algolia (Scout)

| Item                                                           | Status |
| -------------------------------------------------------------- | ------ |
| `laravel/scout`, `algolia/algoliasearch-client-php`            | Done   |
| `SearchableCandidateProfile` on `User`                         | Done   |
| `CandidateBrowseService` → Algolia when configured, else MySQL | Done   |
| `UserObserver` + `SyncProfileToAlgolia` job                    | Done   |
| `php artisan candidates:sync-algolia`                          | Done   |
| Health check `search` service                                  | Done   |
| `.env.example` Scout/Algolia placeholders                      | Done   |
| PHPUnit: `SCOUT_DRIVER=collection`                             | Done   |

**Local Algolia (`.env` only):**

```env
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_write_api_key
SCOUT_CANDIDATE_INDEX=candidates
SCOUT_QUEUE=true
```

After configuring:

```bash
php artisan scout:sync-index-settings
php artisan candidates:sync-algolia
php artisan queue:work redis --queue=critical,high,default,low
```

---

## Related docs

- [Phase 1](./PHASE1_APP_PLAN_ALIGNMENT.md)
- [Phase 2](./PHASE2_APP_PLAN_ALIGNMENT.md)
