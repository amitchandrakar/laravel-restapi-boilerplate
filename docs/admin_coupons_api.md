# Admin Coupons API

Manage registration discount coupons and redemption limits.

## Endpoints

| Method   | Path                           | Permission             |
| -------- | ------------------------------ | ---------------------- |
| `GET`    | `/api/v1/admin/coupons`        | `admin.coupons.view`   |
| `POST`   | `/api/v1/admin/coupons`        | `admin.coupons.add`    |
| `PATCH`  | `/api/v1/admin/coupons/{uuid}` | `admin.coupons.edit`   |
| `DELETE` | `/api/v1/admin/coupons/{uuid}` | `admin.coupons.delete` |

Auth: Bearer Sanctum token required.

## POST body (create)

```json
{
    "code": "SAVE20",
    "name": "Twenty percent off Talash",
    "discountType": "percent",
    "discountValue": 20,
    "packageUuid": "optional-package-uuid",
    "startsAt": "2026-09-01T00:00:00Z",
    "expiresAt": "2026-12-31T23:59:59Z",
    "maxUses": 100,
    "isActive": true
}
```

- `packageUuid` omitted or null applies the coupon to all active packages.
- `startsAt` omitted or null means the coupon is available immediately.
- `discountType`: `percent` or `fixed` (rupees off catalog registration price).
- `maxUses` null means unlimited redemptions.
- When both `startsAt` and `expiresAt` are set, `expiresAt` must be on or after `startsAt`.

## Public validate

`POST /api/v1/app/auth/registration/validate-coupon`

```json
{
    "packageUuid": "package-uuid",
    "couponCode": "SAVE20"
}
```

Response:

```json
{
    "valid": true,
    "originalPrice": 365,
    "discountedPrice": 292,
    "coupon": {
        "uuid": "...",
        "code": "SAVE20",
        "name": "Twenty percent off Talash",
        "discountType": "percent",
        "discountValue": 20
    }
}
```

Registration options (`GET /api/v1/app/auth/registration`) include `availableCoupons[]` on each package.

Register and checkout accept optional `coupon_code`. When the discounted price is zero, subscription activates without payment and redemption is recorded atomically.

## Test command

```bash
php artisan test tests/Feature/AdminCouponsTest.php
```
