# Member onboarding API (`/api/v1/app/me/…`)

Base URL prefix: **`/api/v1`**.

All endpoints below use the standard JSON envelope unless noted:

```json
{
    "success": true,
    "statusCode": 200,
    "message": "…",
    "data": {},
    "error": null,
    "meta": {
        "timestamp": "…",
        "requestId": "…",
        "version": "…"
    }
}
```

Errors use `success: false`, non‑null `error`, and HTTP status matching `statusCode`.

---

## Authentication and headers

| Concern             | Detail                                                                                                   |
| ------------------- | -------------------------------------------------------------------------------------------------------- |
| **Auth**            | `Authorization: Bearer <sanctum_access_token>`                                                           |
| **Session**         | Routes use `auth:sanctum` and `tracked.session` (same as other member APIs).                             |
| **Optional header** | `X-User-Profile-Uuid` — if present, must equal the authenticated user’s `users.uuid`; otherwise **403**. |

---

## Registration checkout

Prepares subscription state and either skips Razorpay or returns checkout fields for the **selected package** (for users who registered via `POST /auth/register` and then choose a plan).

### `POST /me/registration/checkout`

**Method:** `POST`  
**Path:** `/api/v1/app/me/registration/checkout`

**Request body (JSON)**

| Field          | Type          | Required | Description                                   |
| -------------- | ------------- | -------- | --------------------------------------------- |
| `package_uuid` | string (UUID) | Yes      | Must exist on an active, non‑deleted package. |

**Success `200` — skip checkout** (`data`)

When registration amount for the package is **0**, or the user **already has an active subscription** for that package:

```json
{
    "skip_checkout": true,
    "reason": "free_or_complimentary"
}
```

or

```json
{
    "skip_checkout": true,
    "reason": "already_subscribed"
}
```

**Success `200` — Razorpay checkout** (`data`)

When payment is required and a pending order is created or reused:

```json
{
    "skip_checkout": false,
    "order_id": "order_…",
    "key_id": "rzp_…",
    "amount_paise": 36500,
    "currency": "INR",
    "payment_uuid": "…",
    "checkout_options": {
        "method": {
            "upi": true,
            "card": false,
            "netbanking": false,
            "wallet": false,
            "emi": false
        }
    }
}
```

Merge `checkout_options` into Razorpay Checkout `options` on the client (same pattern as [`payment_razorpay_api.md`](payment_razorpay_api.md)).

**Typical errors**

| HTTP  | When                                                       |
| ----- | ---------------------------------------------------------- |
| `401` | Missing or invalid Bearer token.                           |
| `403` | User is not a candidate (or profile UUID header mismatch). |
| `422` | Validation failed (e.g. invalid `package_uuid`).           |

---

## Registration payment verify

Same semantics as `POST /auth/payment/registration/confirm`, exposed under `/me/…` for the mobile contract.

### `POST /me/registration/payments/verify`

**Method:** `POST`  
**Path:** `/api/v1/app/me/registration/payments/verify`

**Request body (JSON)**

| Field                 | Type   | Required |
| --------------------- | ------ | -------- |
| `razorpay_order_id`   | string | Yes      |
| `razorpay_payment_id` | string | Yes      |
| `razorpay_signature`  | string | Yes      |

**Success `200` (`data`)**

```json
{
    "payment_status": "success",
    "permissions": ["candidate.…", "…"]
}
```

**Typical errors**

| HTTP  | When                                                         |
| ----- | ------------------------------------------------------------ |
| `401` | Unauthenticated.                                             |
| `409` | Payment already confirmed or cannot be confirmed (conflict). |
| `422` | Invalid signature or no matching payment for the order.      |

---

## Registration status (onboarding gate)

### `GET /me/registration/status`

**Method:** `GET`  
**Path:** `/api/v1/app/me/registration/status`

**Query parameters**

| Parameter      | Type          | Required | Description                                                                                                                                     |
| -------------- | ------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `package_uuid` | string (UUID) | No       | If omitted, the API infers package context from the user’s latest **pending** subscription, then latest **active** subscription, when possible. |

**Success `200` (`data`) — shape**

```json
{
    "user_uuid": "…",
    "profile_status": "draft",
    "package": {
        "uuid": "…",
        "name": "…",
        "registration_payable_rupees": 0
    },
    "payment": {
        "resolved": true,
        "registration_payable_rupees": 3650,
        "skip_checkout": false,
        "subscription_status": "pending",
        "payment_status": "pending",
        "pending_payment_uuid": "…",
        "gateway_order_id": "order_…",
        "awaiting_checkout": false,
        "reason": "subscription_active"
    },
    "kyc": {
        "status": "not_submitted",
        "document_uuid": null,
        "submitted_at": null
    },
    "next_step": "payment"
}
```

**`next_step` values (machine hint)**

| Value              | Typical meaning                                               |
| ------------------ | ------------------------------------------------------------- |
| `payment`          | Complete or retry paid registration checkout / verify.        |
| `verify_identity`  | KYC not submitted or not approved (e.g. rejected / resubmit). |
| `pending_review`   | KYC submitted, awaiting staff review.                         |
| `complete_profile` | KYC approved but profile not published.                       |
| `done`             | Paid (if applicable), KYC approved, profile published.        |

If no package can be resolved and no query is provided, `payment` may include `resolved: false` and a short `message`.

---

## KYC — multipart flow

Multipart uploads are encoded as **WebP** and saved under the **`user_profile_images`** disk (`public/images/uploads/`), same root as profile photos:

`{user_id}/id_verification/{uuid}.webp`

Persisted values in the database are **relative keys**; API responses expose **absolute URLs** via `KycDocumentResource`. After admin **rejection** or **`resubmission_required`**, the candidate may run **upload-sessions → upload → submit** again; replaced files under `id_verification/` are removed when possible.

### `GET /me/kyc/documents`

**Method:** `GET`  
**Path:** `/api/v1/app/me/kyc/documents`

Returns the same list as `GET /api/v1/app/auth/candidate/kyc/documents` — use this on the ID verification screen to show current images and status (`verificationStatus`, `rejectionReason`).

**Success `200` (`data`):** array of `KycDocumentResource` objects.

---

### `POST /me/kyc/upload-sessions`

**Method:** `POST`  
**Path:** `/api/v1/app/me/kyc/upload-sessions`

**Request body:** none.

**Success `200` (`data`)**

```json
{
    "session_id": "…",
    "expires_in_seconds": 3600,
    "upload_required_fields": ["aadhaar_front", "aadhaar_back", "selfie"]
}
```

---

### `POST /me/kyc/upload`

**Method:** `POST`  
**Path:** `/api/v1/app/me/kyc/upload`

**Request:** `multipart/form-data`

| Field           | Type          | Required | Rules                                                                                     |
| --------------- | ------------- | -------- | ----------------------------------------------------------------------------------------- |
| `session_id`    | string (UUID) | Yes      | From `upload-sessions`.                                                                   |
| `aadhaar_front` | file          | Yes      | Image; max size from `config/kyc_id_verification.php` (`max_upload_kb`, default 5120 KB). |
| `aadhaar_back`  | file          | Yes      | Same                                                                                      |
| `selfie`        | file          | Yes      | Same                                                                                      |

**Success `200` (`data`)**

```json
{
    "session_id": "…",
    "aadhaar_front_url": "https://…/images/uploads/12/id_verification/9ecc5d01-….webp",
    "aadhaar_back_url": "https://…/images/uploads/12/id_verification/….webp",
    "selfie_url": "https://…/images/uploads/12/id_verification/….webp"
}
```

**Typical errors:** `422` if `session_id` is invalid/expired.

---

### `POST /me/kyc/submit`

**Method:** `POST`  
**Path:** `/api/v1/app/me/kyc/submit`

**Request body (JSON)**

| Field                    | Type          | Required |
| ------------------------ | ------------- | -------- |
| `session_id`             | string (UUID) | Yes      |
| `document_number_masked` | string        | No       |

**Success `200` (`data`)** — same shape as `KycDocumentResource` (camelCase):

```json
{
    "uuid": "…",
    "documentType": "aadhaar",
    "documentNumberMasked": "…",
    "documentFrontUrl": "https://…",
    "documentBackUrl": "https://…",
    "selfieUrl": "https://…",
    "verificationStatus": "pending",
    "rejectionReason": null,
    "submittedAt": "…",
    "verifiedAt": null
}
```

The upload session is cleared after successful submit.

---

## Devices (FCM stub)

### `PUT /me/devices`

**Method:** `PUT`  
**Path:** `/api/v1/app/me/devices`

**Request body (JSON)** — all optional

| Field       | Type              |
| ----------- | ----------------- |
| `fcm_token` | string (max 4096) |
| `platform`  | string (max 64)   |
| `device_id` | string (max 255)  |

**Success `200` (`data`)**

```json
{
    "registered": false,
    "stub": true
}
```

Nothing is persisted yet; intended for future push registration.

---

## Razorpay webhook (alias)

Same handler as `POST /payment/razorpay/webhook`.

### `POST /webhooks/razorpay`

**Method:** `POST`  
**Path:** `/api/v1/app/webhooks/razorpay`

**Authentication:** none (public).

**Headers**

| Header                 | Required               |
| ---------------------- | ---------------------- |
| `X-Razorpay-Signature` | Yes (HMAC of raw body) |

**Request body:** raw Razorpay webhook JSON (preserve body for signature verification).

**Success `200`:** processed event (idempotent).

**Typical errors:** `401` invalid signature; `422` JSON/process failure.

---

## Related documentation

- Razorpay env keys, Checkout hints, and legacy confirm/status routes: [`payment_razorpay_api.md`](payment_razorpay_api.md).
- URL‑based KYC (alternative to multipart): `PUT /api/v1/app/auth/candidate/kyc/documents` — see existing candidate/KYC docs.
