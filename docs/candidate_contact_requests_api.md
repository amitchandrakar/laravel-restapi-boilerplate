# Candidate contact requests API

Candidates can ask another candidate for permission to see their **phone number**. Requests are stored in `contact_requests` (`pending` → `accepted` | `rejected`). **Database notifications** inform the recipient when a request arrives, and the requester when a request is **accepted** (no notification on reject).

Related: profile phone visibility on **`GET /api/v1/admin/candidates/{user:uuid}/profile-details`** is documented below.

## Base path and auth

- **Prefix:** `/api/v1/auth/candidate/contact-requests`
- **Authentication:** `Authorization: Bearer <token>`
- **Middleware stack:** `auth:sanctum`, `tracked.session` (same as other member routes under `/api/v1/auth/…`).

Responses use the standard API envelope (`success`, `statusCode`, `message`, `data`, `error`, `meta`). See [`API_DOCUMENTATION_INTRO.md`](API_DOCUMENTATION_INTRO.md).

---

## Permissions and packages

- **Send a request:** the authenticated user must have role **`candidate`** and permission **`candidate.send_contact_requests`** (typically granted via subscription packages such as Talash / Rishta; not on the default Parichay free tier).
- **Respond to a request:** only the **`to_user`** (recipient) may call the respond endpoint; they only need the **`candidate`** role (no extra permission).

---

## 1. Create contact request

**`POST /api/v1/auth/candidate/contact-requests`**

**Middleware:** `permission:candidate.send_contact_requests`

### Request body (JSON)

| Field | Type | Required | Notes |
| ----- | ---- | -------- | ----- |
| `candidateUuid` | string (UUID) | Yes | Target candidate’s `users.uuid`. |
| `requestMessage` | string | No | Max 5000 characters; stored as `request_message`. |

### Behaviour

- Target user must exist and have the **`candidate`** role.
- Cannot target yourself.
- At most **one pending** request per pair `(from_user_id, to_user_id)`; a second pending request returns **422** validation.
- After a **rejected** request, a **new** request may be created (new row).

### Success — **201 Created**

`data` shape:

```json
{
  "uuid": "<contact_request_uuid>",
  "candidateUuid": "<target_user_uuid>",
  "requestStatus": "pending",
  "requestMessage": "...",
  "createdAt": "2026-05-02T12:00:00+00:00"
}
```

### Side effect

- A **database notification** is created for **`to_user`** (see [Notifications](#notifications)).

### Errors

- **403** — Not a candidate, or missing `candidate.send_contact_requests`.
- **422** — Validation (unknown target treated as validation error for `candidateUuid`, duplicate pending, self-request, non-candidate target).

---

## 2. Accept or reject contact request

**`PATCH /api/v1/auth/candidate/contact-requests/{uuid}`**

- `{uuid}` is the **`contact_requests.uuid`** value (not the numeric `id`).

### Request body (JSON)

| Field | Type | Required | Notes |
| ----- | ---- | -------- | ----- |
| `decision` | string | Yes | `accepted` or `rejected`. |
| `responseMessage` | string | No | Max 5000 characters; stored as `response_message`. |

### Behaviour

- Only the **`to_user_id`** of the row may respond.
- Row must be **`pending`**. Otherwise **422**.

### Success — **200 OK**

`data` shape:

```json
{
  "uuid": "<contact_request_uuid>",
  "requestStatus": "accepted",
  "responseMessage": "...",
  "respondedAt": "2026-05-02T12:05:00+00:00"
}
```

### Side effects

- **`accepted`:** a **database notification** is sent to **`from_user`** (see [Notifications](#notifications)).
- **`rejected`:** **no** notification to the requester.

### Errors

- **403** — Authenticated user is not the recipient (`to_user`), or not a candidate.
- **422** — Validation (e.g. not pending, invalid `decision`).

---

## Phone visibility on profile details

Candidates load another member’s full profile via:

**`GET /api/v1/admin/candidates/{user:uuid}/profile-details`**

(Allowed for candidates when [`actorMayViewCandidateProfileDetails`](../app/Http/Controllers/Api/V1/Admin/CandidateUserController.php) passes; same payload shape as admin read-only profile details.)

**Phone masking (peer candidates):**

- If the viewer is **another** **`candidate`**, is **not** viewing their **own** profile, and does **not** have **`admin.candidates.view`**, then top-level **`phone`** and **`sections.personalDetails.phone`** are **`null`** unless there is an **accepted** row in `contact_requests` with:
  - `from_user_id` = viewer’s id  
  - `to_user_id` = profile owner’s id  

**Staff** with `admin.candidates.view` and **self** profile views always see the real phone when set.

**Email** is not masked by this feature (phone only).

---

## Notifications

Notifications use Laravel’s **`notifications`** table (`database` channel). The **`type`** column stores the notification class name (e.g. `App\Notifications\ContactRequestReceivedNotification`). Client UIs usually rely on the JSON **`data`** payload.

### `ContactRequestReceivedNotification` (to `to_user`)

`data` includes (among others):

| Key | Description |
| --- | ----------- |
| `kind` | `contact_request_received` |
| `contact_request_uuid` | Request UUID for PATCH respond. |
| `from_user_uuid` | Requester’s user UUID. |
| `from_user_name` | Requester display name. |
| `request_message` | Optional message from requester. |
| `message` | Human-readable summary. |

### `ContactRequestAcceptedNotification` (to `from_user`)

Sent **only** when `decision` is **`accepted`**. `data` includes:

| Key | Description |
| --- | ----------- |
| `kind` | `contact_request_accepted` |
| `contact_request_uuid` | Request UUID. |
| `to_user_uuid` | Accepter’s user UUID. |
| `to_user_name` | Accepter display name. |
| `message` | Human-readable summary. |

> **Note:** New users may also receive other database notifications (e.g. welcome). Filter by `type` or by `data.kind` when listing.

---

## Data model (reference)

Table **`contact_requests`** (see migration `2026_04_30_107000_create_user_interaction_tables.php`):

- `uuid`, `from_user_id`, `to_user_id`, `request_message`, `request_status` (`pending` | `accepted` | `rejected` | `cancelled`), `responded_at`, `response_message`, timestamps.

---

## Implementation reference

| Piece | Location |
| ----- | -------- |
| HTTP controller | `app/Http/Controllers/Api/V1/CandidateContactRequestController.php` |
| Service | `app/Services/ContactRequestService.php` |
| Model | `app/Models/ContactRequest.php` |
| Form requests | `app/Http/Requests/Api/V1/Candidate/StoreContactRequestRequest.php`, `RespondContactRequestRequest.php` |
| Routes | `routes/api/v1.php` (inside `auth` + `sanctum` + `tracked.session` group) |
| Phone masking | `app/Services/AdminCandidateProfileDetailsService.php` (`profilePhoneForViewer`), `app/Http/Resources/Api/V1/AdminCandidateProfileDetailsResource.php` |
| Feature tests | `tests/Feature/ContactRequestFlowTest.php` |
