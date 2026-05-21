# Admin Payments API

This document describes complete payment CRUD from the admin API.

## Endpoints

- `GET /api/v1/admin/payments` (permission: `admin.payments.view`)
- `GET /api/v1/admin/payments/{paymentUuid}` (permission: `admin.payments.view`)
- `POST /api/v1/admin/payments` (permission: `admin.payments.add`)
- `PATCH /api/v1/admin/payments/{paymentUuid}` (permission: `admin.payments.edit`)
- `PUT /api/v1/admin/payments/{paymentUuid}` (permission: `admin.payments.edit`)
- `DELETE /api/v1/admin/payments/{paymentUuid}` (permission: `admin.payments.delete`, hard delete)

Auth for all endpoints: `Bearer` Sanctum token required.

## Create/Update request body

```json
{
    "user_id": 101,
    "subscription_id": 77,
    "package_id": 5,
    "gateway_name": "razorpay",
    "gateway_order_id": "order_ABC123",
    "gateway_payment_id": "pay_ABC123",
    "gateway_reference_id": "ref_ABC123",
    "amount": 4999,
    "currency": "INR",
    "payment_status": "success",
    "payment_method": "upi",
    "paid_at": "2026-05-01T10:00:00Z",
    "failed_reason": null,
    "raw_response_json": {
        "status": "captured"
    }
}
```

For update, all fields are optional (`PATCH` semantics).

## Validation rules

- `user_id`: required on create, exists in `users.id`
- `subscription_id`: nullable, exists in `subscriptions.id`
- `package_id`: required on create, exists in `packages.id`
- `gateway_name`: nullable string, max 64
- `gateway_order_id`/`gateway_payment_id`/`gateway_reference_id`: nullable string, max 255
- `amount`: required on create, numeric, minimum 0
- `currency`: nullable on create, required when provided for update, size 3 (stored uppercase)
- `payment_status`: one of `pending|success|failed|refunded|cancelled`
- `payment_method`: one of `upi|card|netbanking|wallet|cash|manual`
- `paid_at`: nullable date
- `failed_reason`: nullable string
- `raw_response_json`: nullable object

## Response shape (create/show/update)

```json
{
    "success": true,
    "statusCode": 201,
    "message": "Payment created successfully",
    "data": {
        "id": 1,
        "uuid": "....",
        "userId": 101,
        "subscriptionId": 77,
        "packageId": 5,
        "gatewayName": "razorpay",
        "gatewayOrderId": "order_ABC123",
        "gatewayPaymentId": "pay_ABC123",
        "gatewayReferenceId": "ref_ABC123",
        "amount": 4999,
        "currency": "INR",
        "paymentStatus": "success",
        "paymentMethod": "upi",
        "paidAt": "2026-05-01T10:00:00.000000Z",
        "failedReason": null,
        "rawResponse": {
            "status": "captured"
        },
        "createdAt": "2026-05-01T10:00:00.000000Z",
        "updatedAt": "2026-05-01T10:00:00.000000Z"
    }
}
```

## List query parameters

| Parameter               | Type   | Description                                   |
| ----------------------- | ------ | --------------------------------------------- |
| `perPage`               | int    | 1–100                                         |
| `search`                | string | Gateway ids or payer email                    |
| `user_id`               | int    | Payer user id                                 |
| `package_id`            | int    | Package id                                    |
| `payment_status`        | string | pending, success, failed, refunded, cancelled |
| `gateway_name`          | string | e.g. razorpay                                 |
| `payment_method`        | string | upi, card, netbanking, wallet, cash, manual   |
| `paid_from` / `paid_to` | date   | Paid-at range                                 |
| `sort`                  | string | latest (default), oldest, amount              |

## List response

`GET /api/v1/admin/payments?perPage=15` returns payment rows with embedded `candidate` (uuid, fullName, profilePhoto, email) and `packageName`, plus pagination in `meta.pagination`.

## Side effects on create/update

- If `subscription_id` is present and subscription exists:
    - `subscriptions.last_payment_id` is updated to the current payment id.
    - `subscriptions.subscription_status` is updated from payment status:
        - `success` -> `active`
        - `refunded` -> `cancelled`
        - `failed|cancelled|pending` -> `pending`
- A `user_payment_history` row is inserted for each create/update:
    - `pending` -> `initiated`
    - `success` -> `confirmed`
    - `failed|cancelled` -> `failed`
    - `refunded` -> `refund_initiated` (or `refunded` if status was already refunded)

## Delete response

`DELETE /api/v1/admin/payments/{paymentUuid}` returns success envelope with message `Payment deleted successfully`.

Delete is hard-delete (row removed from `payments`).

## Test command

```bash
php artisan test tests/Feature/AdminPaymentCreateTest.php tests/Feature/AdminPaymentCrudTest.php
```
