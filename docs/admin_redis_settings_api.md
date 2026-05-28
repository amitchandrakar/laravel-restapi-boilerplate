# Admin Redis Settings API

Operational reference for Redis in `redis_settings`. **Runtime cache/session/queue still use `.env` in v1** (no hot reconnect).

## Endpoints

- `GET /api/v1/admin/settings/redis` (`admin.settings.redis.view`)
- `PUT /api/v1/admin/settings/redis` (`admin.settings.redis.edit`)

`password` is masked on GET.

## Test

```bash
php artisan test tests/Feature/AdminRedisSettingsTest.php
```
