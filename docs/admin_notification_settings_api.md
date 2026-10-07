# Admin Notification Settings API

Email SMTP, Twilio SMS, and FCM push settings in `notification_settings`.

## Mail source by environment

| `APP_ENV`                                          | SMTP credentials                      | `emailEnabled`                                                |
| -------------------------------------------------- | ------------------------------------- | ------------------------------------------------------------- |
| `local`                                            | `.env` `MAIL_*` via `config/mail.php` | Ignored — mail channel always allowed                         |
| `staging`, `production`, and any other non-`local` | `notification_settings` only          | Must be `true` with a non-empty `mailHost` or mail is skipped |

When email is disabled (or SMTP host is empty) in a non-local environment, runtime `mail.default` is forced to `array` so leftover `.env` SMTP is never used for notification email. Database notification channels still work.

## Endpoints

- `GET /api/v1/admin/settings/notifications` (`admin.settings.notifications.view`)
- `PUT /api/v1/admin/settings/notifications` (`admin.settings.notifications.edit`)

Passwords and tokens are masked on GET. Saving notification settings dispatches a job that re-applies runtime mail config.

## Test

```bash
php artisan test tests/Feature/AdminNotificationSettingsTest.php tests/Feature/MailDeliveryTest.php
```
