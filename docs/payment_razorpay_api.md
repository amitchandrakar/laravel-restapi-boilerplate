# Razorpay — candidate registration payment (UPI)

Paid candidate packages use **Razorpay Orders + Checkout JS**, restricted to **UPI** on the frontend. **Free packages** (payable amount ≤ 0, e.g. `PARICHAY_FREE`) skip Razorpay: subscription is **active** immediately and package permissions are synced as today.

## Environment

Set in `.env` (see `.env.example`):

| Variable                  | Purpose                                           |
| ------------------------- | ------------------------------------------------- |
| `RAZORPAY_KEY_ID`         | Publishable key (Checkout `key`)                  |
| `RAZORPAY_KEY_SECRET`     | Server secret (order create + signature verify)   |
| `RAZORPAY_WEBHOOK_SECRET` | Webhook signing secret for `X-Razorpay-Signature` |
| `RAZORPAY_CURRENCY`       | Default `INR`                                     |

Config: `config/services.php` → `razorpay`.

## Registration response shape

`POST /api/v1/auth/register-candidate`

- **Free package:** `201` with `token`, `user`, `permissions` (package entitlements). No `payment` object.
- **Paid package:** `201` with `token`, `user`, base candidate permissions only; subscription is **pending** until payment succeeds. Includes:

```json
{
    "data": {
        "user": { "...": "..." },
        "token": "...",
        "permissions": ["..."],
        "payment": {
            "paymentUuid": "…",
            "orderId": "order_…",
            "keyId": "rzp_…",
            "amount": 99900,
            "currency": "INR",
            "packageName": "…",
            "checkoutOptions": {
                "method": {
                    "upi": true,
                    "card": false,
                    "netbanking": false,
                    "wallet": false,
                    "emi": false
                }
            }
        }
    }
}
```

`checkoutOptions` comes from `config/services.php` → `razorpay.checkout`. **Merge** this object into Razorpay Checkout `options` so only UPI is offered (see below).

`amount` is in **paise** (integer), matching Razorpay order amount.

## Confirm payment (after Checkout success)

**Auth:** `Authorization: Bearer <token>` (same token from registration).

`POST /api/v1/auth/payment/registration/confirm`

**Body (JSON):**

| Field                 | Type   | Description                           |
| --------------------- | ------ | ------------------------------------- |
| `razorpay_order_id`   | string | Order id from registration / Checkout |
| `razorpay_payment_id` | string | Payment id from Checkout `handler`    |
| `razorpay_signature`  | string | Signature from Checkout `handler`     |

**Success `200`:** activates subscription (idempotent if already success), syncs package permissions, may enqueue `PaymentSucceededNotification`.

```json
{
    "data": {
        "paymentStatus": "success",
        "subscription": { "status": "active", "endsAt": "2027-01-01T00:00:00+00:00" },
        "permissions": ["…"]
    }
}
```

**Errors:** `401` unauthenticated; `422` validation / invalid signature; `403` if payment belongs to another user; `409` if payment is not in a confirmable state (e.g. already finalized differently).

## Payment status (polling)

`GET /api/v1/auth/payment/registration/{paymentUuid}/status`

Returns current `paymentStatus`, `orderId`, and linked `subscription` summary for the authenticated user.

## Webhook (server-to-server)

`POST /api/v1/payment/razorpay/webhook`

- **Public** (no Bearer). Throttled.
- Header: `X-Razorpay-Signature` — HMAC of **raw body** with `RAZORPAY_WEBHOOK_SECRET`.
- Invalid signature → `401`.

Handled events (idempotent; first success wins):

- `payment.captured` — treat as success when payload matches a pending registration payment by `order_id`.
- `payment.failed` — marks payment failed and notifies where applicable.

Duplicate webhook deliveries are deduped using Razorpay event `id` stored on `payments.webhook_event_id`.

### Sample `payment.captured` (truncated)

```json
{
    "entity": "event",
    "account_id": "acc_xxx",
    "event": "payment.captured",
    "contains": ["payment"],
    "payload": {
        "payment": {
            "entity": {
                "id": "pay_xxx",
                "entity": "payment",
                "amount": 99900,
                "currency": "INR",
                "status": "captured",
                "order_id": "order_xxx"
            }
        }
    },
    "created_at": 1710000000
}
```

Razorpay also sends a top-level `id` for the event (e.g. `evt_xxx`) used for idempotency.

## Frontend — Razorpay Checkout (UPI only)

Use the `orderId` and `keyId` from the registration `payment` block. The API returns **`checkoutOptions`** (same shape as below); merge it into Checkout options so **only UPI** is enabled:

```javascript
const options = {
    key: keyId,
    amount: amount, // paise, must match order
    currency: currency,
    name: 'Alonti',
    description: packageName,
    order_id: orderId,
    ...(checkoutOptions || {}),
    handler: async function (response) {
        await fetch('/api/v1/auth/payment/registration/confirm', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: 'Bearer ' + token
            },
            body: JSON.stringify({
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature
            })
        });
    },
    modal: {
        ondismiss: function () {
            /* user closed */
        }
    }
};
const rzp = new Razorpay(options);
rzp.open();
```

## Member notifications

On success: feed kind `payment_succeeded`. On failure (webhook path): `payment_failed`. After admin or self **publish** of profile: `profile_published`. See `[member_notifications_api.md](member_notifications_api.md)`.

## Product rules (summary)

- Subscription stays `**pending`** until payment is **success\*\*; package feature permissions apply only after success.
- Confirmation is **dual-path**: client `confirm` + webhook; both are idempotent.
- **Refunds / renewals** are out of scope for this v1 registration flow.
