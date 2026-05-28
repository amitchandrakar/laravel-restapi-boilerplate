# Admin Payment Gateway Settings API

Singleton Razorpay configuration stored in `payment_gateway_settings`.

## Endpoints

- `GET /api/v1/admin/settings/payments` (`admin.settings.payments.view`)
- `PUT /api/v1/admin/settings/payments` (`admin.settings.payments.edit`)

Secrets (`liveKeySecret`, `sandboxKeySecret`, `webhookSecret`) are masked on GET (`hasLiveKeySecret`, etc.). Omit or send `***` on PUT to leave unchanged.

When `isEnabled` is true, `ApplySettingsConfigJob` merges values into `config('services.razorpay')` (with `.env` fallback when disabled).

## Test

```bash
php artisan test tests/Feature/AdminPaymentGatewaySettingsTest.php
```
