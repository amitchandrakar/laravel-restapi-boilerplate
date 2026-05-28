# Admin Search Settings API

Algolia / Scout settings in `search_settings`.

## Endpoints

- `GET /api/v1/admin/settings/search` (`admin.settings.search.view`)
- `PUT /api/v1/admin/settings/search` (`admin.settings.search.edit`)

API keys are masked on GET. When `isEnabled` is true, Scout Algolia config is applied from the database row.

## Test

```bash
php artisan test tests/Feature/AdminSearchSettingsTest.php
```
