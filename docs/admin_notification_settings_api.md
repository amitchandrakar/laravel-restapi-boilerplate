# Admin Notification Settings API

Email SMTP, Twilio SMS, and FCM push settings in `notification_settings`.

## Endpoints

- `GET /api/v1/admin/settings/notifications` (`admin.settings.notifications.view`)
- `PUT /api/v1/admin/settings/notifications` (`admin.settings.notifications.edit`)

Passwords and tokens are masked on GET. When `emailEnabled` is true, runtime mail config is applied from the database row.

## Test

```bash
php artisan test tests/Feature/AdminNotificationSettingsTest.php
```
