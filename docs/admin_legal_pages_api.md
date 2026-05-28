# Admin Legal Pages API

CRUD-by-slug for `legal_pages` (terms, privacy-policy, cookie-policy seeded by default).

## Endpoints

- `GET /api/v1/admin/settings/legal-pages` (`admin.settings.legal.view`)
- `GET /api/v1/admin/settings/legal-pages/{slug}` (`admin.settings.legal.view`)
- `PUT /api/v1/admin/settings/legal-pages/{slug}` (`admin.settings.legal.edit`)

## PUT body (partial)

```json
{
    "title": "Privacy Policy",
    "body": "<p>Content</p>",
    "version": "1.0",
    "isPublished": true,
    "publishedAt": "2026-05-20T10:00:00Z"
}
```

## Test

```bash
php artisan test tests/Feature/AdminLegalPagesTest.php
```
