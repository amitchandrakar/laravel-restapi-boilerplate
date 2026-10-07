# Admin moderation reports API

Operational moderation queues for spam reports, favorites, and contact requests. Separate from analytics reports under `/api/v1/admin/reports/*`.

## Permissions

| Permission                        | Purpose                                                  |
| --------------------------------- | -------------------------------------------------------- |
| `admin.moderation_reports.view`   | List moderation queues                                   |
| `admin.moderation_reports.action` | Resolve spam, unmark favorites, resolve contact requests |

## Spam reports

### Member: report profile

**`POST /api/v1/app/auth/candidate/{uuid}/report-spam`**

Body:

```json
{ "reason": "Suspicious payment request" }
```

Creates a row in `profile_spam_reports` with `status=pending`. Duplicate pending reports from the same reporter for the same profile are rejected.

While a report from the member is open (`pending`, `reviewing`, or `action_taken`), that reported profile is excluded from **that reporter’s** browse, favorites, matches, and authenticated featured lists. Other members still see the profile until an admin marks them as spam. After admin dismiss (`dismissed`), the profile may reappear for the original reporter.

### Admin: list spam reports

**`GET /api/v1/admin/moderation-reports/spam`**

Query: `page`, `per_page`, `search`, `status` (`pending`, `action_taken`, `dismissed`, …)

Response items include `reportedBy`, `reportedPerson` (candidate card summaries), `reason`, `reportedAt`, `status`.

### Admin: mark spammer

**`POST /api/v1/admin/moderation-reports/spam/{uuid}/mark-spammer`**

- Sets reported user `profile_status=spam`, `status=inactive`
- Sets report `status=action_taken`
- Blocks future login via `AuthService::login`

### Admin: dismiss report

**`POST /api/v1/admin/moderation-reports/spam/{uuid}/mark-not-spammer`**

- Sets report `status=dismissed`
- If no other open reports and profile was `spam`, restores `profile_status` (published when `published_at` set, else draft) and `status=active`

## Favorites report

### Admin: list favorites

**`GET /api/v1/admin/moderation-reports/favorites`**

Query: `page`, `per_page`, `search`

Response items: `markedBy`, `markedPerson`, `markedAt`, `source`.

### Admin: unmark favorite

**`POST /api/v1/admin/moderation-reports/favorites/{uuid}/unmark`**

Soft-deletes the `favorites` row (`deleted_at`). Member favorites APIs already exclude soft-deleted rows.

## Contact requests report

### Schema

`contact_requests` includes:

| Column              | Values                                    |
| ------------------- | ----------------------------------------- |
| `admin_resolution`  | `contacted` \| `not_contacted` (nullable) |
| `admin_resolved_at` | timestamp                                 |
| `admin_resolved_by` | `users.id`                                |

### Admin: list contact requests

**`GET /api/v1/admin/moderation-reports/contact-requests`**

Query: `page`, `per_page`, `search`, `status` (maps to `request_status`)

### Admin: mark contacted

**`POST /api/v1/admin/moderation-reports/contact-requests/{uuid}/mark-contacted`**

Sets `admin_resolution=contacted`. Hides the row from the member **sent** list.

### Admin: mark not contacted

**`POST /api/v1/admin/moderation-reports/contact-requests/{uuid}/mark-not-contacted`**

Sets `admin_resolution=not_contacted`. Row remains visible in the member sent list.

### Member: sent contact requests

**`GET /api/v1/app/auth/candidate/contact-requests`**

Returns outbound requests for the authenticated candidate, **excluding** rows where `admin_resolution=contacted`.

## Don’t show again (mute)

Quiet mute separate from spam. Uses `profile_do_not_show` (`user_id` → `hidden_user_id`).

### Member: hide profile

**`POST /api/v1/app/auth/candidate/{uuid}/dont-show-again`**

Idempotent: returns the existing row if already muted.

Hidden profiles are excluded from **that viewer’s** browse, favorites, matches, and authenticated featured lists (alongside open spam reports).

### Member: unhide profile

**`DELETE /api/v1/app/auth/candidate/{uuid}/dont-show-again`**

### Admin: list don’t-show-again marks

**`GET /api/v1/admin/moderation-reports/dont-show-again`**

Query: `page`, `per_page`, `search`

Response items include `markedBy`, `markedPerson`, `reason`, `markedAt`.

### Admin: unmark

**`POST /api/v1/admin/moderation-reports/dont-show-again/{uuid}/unmark`**

Deletes the mute row so the profile can reappear in the marker’s discovery.
