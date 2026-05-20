# Password reset and forgot-password API

Members can reset their password in two ways:

1. **Guest flow** — after **`POST /api/v1/app/auth/forgot-password`**, the user receives an email with a reset link; the client submits the **email**, **plain token** from that flow, and the **new password** to **`POST /api/v1/app/auth/reset-password`** (no Bearer token).
2. **Authenticated flow** — while logged in, the same **`POST /api/v1/app/auth/reset-password`** accepts **`Authorization: Bearer <token>`** plus **current password** and **new password** (tracked session must be active; see below).

For “change password” from settings without reusing the reset endpoint, **`POST /api/v1/app/auth/change-password`** remains available (requires Bearer + tracked session only; same validation semantics as the authenticated reset branch).

Responses use the standard API envelope (`success`, `statusCode`, `message`, `data`, `error`, `meta`). See [`API_DOCUMENTATION_INTRO.md`](API_DOCUMENTATION_INTRO.md).

---

## Base paths

| Endpoint            | Path                                    |
| ------------------- | --------------------------------------- |
| Request reset email | `POST /api/v1/app/auth/forgot-password` |
| Apply new password  | `POST /api/v1/app/auth/reset-password`  |

---

## 1. Forgot password (email + token issuance)

**`POST /api/v1/app/auth/forgot-password`**

**Authentication:** none.

### Request body (JSON)

| Field   | Type   | Required | Notes                                                      |
| ------- | ------ | -------- | ---------------------------------------------------------- |
| `email` | string | Yes      | Must exist on `users.email` or validation returns **422**. |

### Behaviour

- If the user exists, a row is **upserted** in `password_reset_tokens` (hashed token, `created_at`), and `ForgotPasswordRequestedEvent` runs (queued listener sends email with a **plain** token embedded in the reset URL).
- Email template uses `config('app.frontend_reset_password_url')` when set; otherwise falls back to `app.url` + `/reset-password` (see `ForgotPasswordNotification`).

### Success — **200 OK**

- `data` is typically `null`.
- `message`: e.g. password reset link sent (same generic copy regardless of whether the email existed — **not** applicable here because unknown emails fail validation).

### Errors

- **422** — Unknown or invalid `email` (`exists:users,email`).

---

## 2. Reset password (guest or authenticated)

**`POST /api/v1/app/auth/reset-password`**

**Middleware:** `optional.sanctum`, `tracked.session`

- **No `Authorization` header:** treated as **guest**; tracked-session middleware does nothing (no user).
- **`Authorization: Bearer <token>` present:** token must be valid and non-expired (**401** if not). User must have an **active tracked session** for that token (**403** if session missing/expired — same as other member routes using `tracked.session`).

Validation rules depend on whether a user was resolved from the Bearer token.

### 2a. Guest — body after forgot-password

| Field      | Type   | Required | Notes                                                                                                          |
| ---------- | ------ | -------- | -------------------------------------------------------------------------------------------------------------- |
| `email`    | string | Yes      | Must exist on `users.email`.                                                                                   |
| `token`    | string | Yes      | **Plain** token from the forgot-password email (server compares with hashed value in `password_reset_tokens`). |
| `password` | string | Yes      | Min 8 characters; must match `password_confirmation`.                                                          |

### 2a. Guest — behaviour

- Verifies token with `Hash::check` and checks expiry against `config('auth.passwords.users.expire')` (minutes; default **60**).
- Updates `users.password`, **deletes** the `password_reset_tokens` row for that email, and **revokes all** Sanctum personal access tokens for that user.

### 2a. Guest — success — **200 OK**

- `data`: `null`.
- `message`: password reset successfully.

### 2a. Guest — errors

- **400** — Invalid or expired reset token (wrong email/token pair or past expiry).
- **422** — Validation.

### 2b. Authenticated — body (Bearer required)

| Field              | Type   | Required | Notes                                                                                                |
| ------------------ | ------ | -------- | ---------------------------------------------------------------------------------------------------- |
| `current_password` | string | Yes      | Must match the user’s current password.                                                              |
| `password`         | string | Yes      | Min 8 characters; must match `password_confirmation`; must be **different** from `current_password`. |

Do not send `email` / `token` for this branch; they are not required when the user is authenticated.

### 2b. Authenticated — behaviour

- Delegates to `AuthService::changePassword`: verifies **current password**, updates password, revokes **other** Sanctum tokens but **keeps the current** session token so the client stays logged in on this device.

### 2b. Authenticated — success — **200 OK**

- Same envelope as guest (`data` null, success message).

### 2b. Authenticated — errors

- **401** — Missing/invalid/expired Bearer token (when a Bearer value was sent).
- **403** — Tracked session invalid; or **current password incorrect** (`message` e.g. “Current password is incorrect”).
- **422** — Validation.

---

## Related: change password (authenticated only)

**`POST /api/v1/app/auth/change-password`**

- **Middleware:** `auth:sanctum`, `tracked.session`
- **Body:** `current_password`, `password`, `password_confirmation` (same rules as authenticated reset).
- **Behaviour:** Same service method as authenticated **`reset-password`** (other tokens revoked, current kept).

Clients may standardize on **`reset-password`** for both flows or keep **`change-password`** for in-app “change password” screens only.

---

## Implementation references

- Controller: `App\Http\Controllers\Api\V1\AuthController` (`forgotPassword`, `resetPassword`, `changePassword`).
- Requests: `ForgotPasswordRequest`, `ResetPasswordRequest`.
- Middleware: `OptionalSanctumAuthentication` (`optional.sanctum`), `EnsureActiveTrackedSession` (`tracked.session`).
- Tokens table: `password_reset_tokens` (migration under `database/migrations`).

---

## Tests

Feature coverage: `tests/Feature/AuthFlowTest.php` (forgot + guest reset, authenticated reset, wrong current password, invalid Bearer on reset).
