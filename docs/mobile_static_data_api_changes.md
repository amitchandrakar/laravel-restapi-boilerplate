## Mobile static data → API changes (backend)

This document describes the backend changes shipped to help the mobile app remove remaining static/mock data on **Home**, **Refer & Earn**, and **Settings**.

All responses follow the existing API envelope:

```json
{
    "success": true,
    "statusCode": 200,
    "message": "OK",
    "data": {}
}
```

---

## 1) Home — activity tiles

### Endpoint

- **Method**: `GET`
- **Path**: `/api/v1/auth/notifications/summary`
- **Auth**: required (`Authorization: Bearer <token>`)

### Request

- **Body**: none
- **Query**: none

### Response (extended)

- **Backward compatible**: existing clients that only read `data.unreadCount` still work.
- **Stats window**: **lifetime** totals.

```json
{
    "success": true,
    "statusCode": 200,
    "message": "Notification summary fetched successfully",
    "data": {
        "unreadCount": 3,
        "stats": {
            "profileViews": 42,
            "contactRequestsSent": 5,
            "contactRequestsReceived": 8,
            "contactRequestsApproved": 3,
            "contactRequestsDeclined": 2,
            "favorites": 12,
            "matches": 7
        }
    }
}
```

### Backend logic (source tables)

- `profileViews`: `profile_views` distinct count of `viewer_user_id` where `viewed_user_id = me`
- `contactRequestsSent`: `contact_requests` count where `from_user_id = me`
- `contactRequestsReceived`: `contact_requests` count where `to_user_id = me`
- `contactRequestsApproved`: `contact_requests` count where `to_user_id = me` and `request_status = accepted`
- `contactRequestsDeclined`: `contact_requests` count where `to_user_id = me` and `request_status = rejected`
- `favorites`: `favorites` count where `user_id = me` and `deleted_at is null`
- `matches`: `matches` count where `user_id = me` and `match_status = active`

---

## 2) Home — featured carousel match badge

### Endpoint

- **Method**: `GET`
- **Path**: `/api/v1/public/featured-candidates`
- **Auth**: optional
    - Anonymous callers: OK
    - Authenticated callers: OK (adds `matchPercentage`)

### Request

- **Body**: none
- **Query**: optional `perPage` (existing)

### Response (extended)

- **Backward compatible**: anonymous callers still get the same list; we only add fields.

```json
{
    "success": true,
    "statusCode": 200,
    "message": "Featured candidates fetched successfully",
    "data": [
        {
            "uuid": "cand_uuid",
            "firstName": "Sneha",
            "lastName": "Pandey",
            "photoUrl": "/storage/photos/sneha.jpg",
            "currentCity": "Raipur",
            "currentState": "Chhattisgarh",
            "age": 24,
            "dateOfBirth": "2001-07-12",
            "matchPercentage": 87
        }
    ]
}
```

### Backend logic

- If a valid bearer token is present, the endpoint looks up match score from `matches`:
    - `matches.user_id = viewer.id`
    - `matches.matched_user_id = featuredCandidate.id`
    - `matches.match_status = active`
    - `matchPercentage = matches.match_score` (clamped to 0–100), else `null` when no row exists.
- If token is missing or invalid: endpoint remains public and returns `matchPercentage: null`.

---

## 3) Refer & Earn (referral block)

### Endpoint

- **Method**: `GET`
- **Path**: `/api/v1/admin/candidates/{uuid}/profile-details`
- **Auth**: required (`Authorization: Bearer <token>`)
- **Scope**: `referral` is only included when `{uuid}` is the authenticated user’s own UUID.

### Response (additions only)

```json
{
    "success": true,
    "statusCode": 200,
    "message": "Candidate profile details fetched successfully",
    "data": {
        "uuid": "cand_self_uuid",
        "referral": {
            "code": "VERMA-01AB23CD",
            "shareUrl": "https://your-app.example/r/VERMA-01AB23CD",
            "rewardSummary": {
                "successfulReferrals": 1,
                "rewardMonthsEarned": 1
            },
            "entries": [
                {
                    "id": "ref_uuid",
                    "name": "Ritika Verma",
                    "invitedAt": "2026-04-21 10:11:12",
                    "status": "rewardEligible"
                }
            ]
        }
    }
}
```

### Backend data model

- `users.referral_code` (nullable, unique)
- `referral_entries` table:
    - `uuid`
    - `inviter_user_id`
    - `invitee_name`
    - `invited_at`
    - `status` (`invited|joined|rewardEligible`)

Notes:

- If `users.referral_code` is missing, the backend generates and persists one on first self `profile-details` fetch.
- `rewardSummary.rewardMonthsEarned` is currently computed as `count(entries where status=rewardEligible)` (1 month per eligible referral).

---

## 4) Settings — preferences (read + write)

### Read (additions only)

- **Method**: `GET`
- **Path**: `/api/v1/admin/candidates/{uuid}/profile-details`
- **Scope**: `preferences` is only included when `{uuid}` is the authenticated user’s own UUID.

```json
{
    "success": true,
    "statusCode": 200,
    "data": {
        "uuid": "cand_self_uuid",
        "preferences": {
            "phoneAlertsEnabled": false,
            "emailNotificationsEnabled": true,
            "showOnlineStatus": false,
            "hidePhoneNumber": true
        }
    }
}
```

### Write

- **Method**: `PATCH` (also accepts `PUT`)
- **Path**: `/api/v1/admin/candidates/{uuid}/sections/preferences`
- **Auth**: required
- **Authorization rules**:
    - Admins with `admin.candidates.edit` can update any candidate
    - Candidates can only update **their own** `{uuid}`; otherwise `403`

### Request body (partial updates allowed)

```json
{
    "phoneAlertsEnabled": true
}
```

### Response

```json
{
    "success": true,
    "statusCode": 200,
    "message": "Preferences updated",
    "data": {
        "preferences": {
            "phoneAlertsEnabled": true,
            "emailNotificationsEnabled": true,
            "showOnlineStatus": false,
            "hidePhoneNumber": true
        }
    }
}
```

### Backend persistence

Stored on `users`:

- `phone_alerts_enabled` (default `false`)
- `email_notifications_enabled` (default `true`)
- `show_online_status` (default `false`)
- `hide_phone_number` (default `true`)
