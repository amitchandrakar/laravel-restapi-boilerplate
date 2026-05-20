# Member notifications API

In-app notification feed for authenticated members (candidates). Uses Laravel’s `notifications` table; only rows with a **`data.kind`** in the product allowlist are returned (contact requests, new matches, profile views). Other database notifications (e.g. welcome) are omitted from this feed.

**Auth:** `Authorization: Bearer <token>` plus **`tracked.session`** (same as other `/api/v1/app/auth/…` member routes).

**Base path:** `/api/v1/app/auth/notifications`

See [`API_DOCUMENTATION_INTRO.md`](API_DOCUMENTATION_INTRO.md) for the standard JSON envelope.

### Demo / local sample data

After [`DemoUsersSeeder`](../database/seeders/DemoUsersSeeder.php), [`DemoCandidateNotificationsSeeder`](../database/seeders/DemoCandidateNotificationsSeeder.php) inserts sample feed notifications (and matching `contact_requests` rows) for demo candidates such as Arjun, Priya, Rohan, and Ananya. It is **idempotent** for those users’ feed kinds. Run alone with `php artisan db:seed --class=DemoCandidateNotificationsSeeder` once demo users exist.

---

## List notifications

**`GET /api/v1/app/auth/notifications`**

**Throttle:** 60 requests per minute (per route group).

### Query parameters

| Parameter    | Type    | Default | Notes                                    |
| ------------ | ------- | ------- | ---------------------------------------- |
| `perPage`    | integer | 15      | 1–50                                     |
| `page`       | integer | 1       |                                          |
| `unreadOnly` | boolean | false   | When true, only rows with `read_at` null |

### Response `data`

Array of items, each shaped as:

| Field                     | Type                      | Notes                                                                                                                                                                             |
| ------------------------- | ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                      | string (UUID)             | Notification id                                                                                                                                                                   |
| `kind`                    | string                    | e.g. `contact_request_received`, `contact_request_accepted`, `new_match`, `profile_viewed`                                                                                        |
| `message`                 | string                    | Human-readable line                                                                                                                                                               |
| `iconKey`                 | string                    | Client mapping hint (`contact_request`, `new_match`, `profile_viewed`, `default`)                                                                                                 |
| `profileImageUrl`         | string                    | Absolute URL for the **related user’s** profile photo (sender, matched user, viewer, or accepter—by `kind`); defaults to the same placeholder as discovery cards when none is set |
| `contactRequestStatus`    | string \| null            | For contact-request notifications: current `contact_requests.request_status` (live)                                                                                               |
| `contactRequestUpdatedAt` | string (ISO 8601) \| null | For contact-request notifications: current `contact_requests.updated_at` (live)                                                                                                   |
| `createdAt`               | string (ISO 8601)         |                                                                                                                                                                                   |
| `readAt`                  | string (ISO 8601) \| null |                                                                                                                                                                                   |
| `data`                    | object                    | Stored payload with **camelCase** keys                                                                                                                                            |
| `actions`                 | array                     | Server-derived CTAs (method, path, optional `body`)                                                                                                                               |

### Response `meta`

- `unreadCount` — count of unread feed notifications (same kind filter as list).
- `pagination` — `page`, `limit`, `total`, `totalPages`, `hasNext`, `hasPrev`.

---

## Unread summary

**`GET /api/v1/app/auth/notifications/summary`**

**`data`:** `{ "unreadCount": <integer> }`

---

## Single notification

**`GET /api/v1/app/auth/notifications/{notificationId}`**

Returns one feed item (same shape as list elements). **404** if missing or not a feed kind.

---

## Mark one as read

**`PATCH /api/v1/app/auth/notifications/{notificationId}/read`**

**403** if the notification belongs to another user.

---

## Mark all feed notifications read

**`POST /api/v1/app/auth/notifications/read-all`**

Sets `read_at` for all unread notifications whose `data.kind` is in the feed allowlist.

---

## `actions` examples

- **`contact_request_received`:** `PATCH` actions targeting `/api/v1/app/auth/candidate/contact-requests/{uuid}` with `body.decision` `accepted` or `rejected`.
- **`new_match`:** `GET` `/api/v1/app/auth/candidate/matches`.
- **`profile_viewed` / `contact_request_accepted`:** `GET` profile-details path for the relevant user UUID.

---

## Related behaviour

- **Profile views:** Candidate A viewing candidate B’s **`GET /api/v1/admin/candidates/{uuid}/profile-details`** records a `profile_views` row and may create a **`profile_viewed`** notification for B (deduplicated per A/B pair for 24 hours).
- **New matches:** Call `App\Services\MatchNotificationService::notifyBothUsersOfMatch(...)` when inserting `matches` rows so both users receive **`new_match`** (no automatic hook in batch jobs until that pipeline calls the service).

---

## Implementation

- `App\Http\Controllers\Api\V1\MemberNotificationController`
- `App\Services\MemberNotificationFeedService`
- Tests: `tests/Feature/MemberNotificationsTest.php`
