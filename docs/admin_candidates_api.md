# Admin candidates API

Base path: `/api/v1/admin/candidates` (Sanctum + `tracked.session` + Spatie permissions).

## List views (buckets)

`GET /api/v1/admin/candidates` — permission: `admin.candidates.view`

Query parameters (validated):

| Parameter                  | Description                                                                              |
| -------------------------- | ---------------------------------------------------------------------------------------- |
| `perPage`                  | 1–100 (default 15)                                                                       |
| `page`                     | Page number                                                                              |
| `bucket`                   | `all` (default), `published`, `under_review`, `suspended`, `featured`, `spam`, `deleted` |
| `search`                   | Matches email, phone, first/last name                                                    |
| `gender`, `marital_status` | Exact filters                                                                            |
| `profile_status`           | Further narrow within bucket                                                             |
| `is_featured`              | Boolean                                                                                  |
| `sort`                     | `latest`, `oldest`, `name`, `published_at`                                               |

Admin panel tabs map to buckets:

- **All Candidates** → `bucket=all`
- **Published Profiles** → `bucket=published`
- **Under Review** → `bucket=under_review`
- **Suspended Profiles** → `bucket=suspended`
- **Featured Profiles** → `bucket=featured`
- **Spam Marked** → `bucket=spam`
- **Deleted Profiles** → `bucket=deleted`

## CRUD

| Method   | Path                                | Permission                | Notes                                |
| -------- | ----------------------------------- | ------------------------- | ------------------------------------ |
| `GET`    | `/candidates`                       | `admin.candidates.view`   | Paginated index                      |
| `POST`   | `/candidates`                       | `admin.candidates.add`    | Basic create (validated)             |
| `GET`    | `/candidates/{uuid}`                | `admin.candidates.view`   | Summary resource                     |
| `PATCH`  | `/candidates/{uuid}`                | `admin.candidates.edit`   | Partial update                       |
| `DELETE` | `/candidates/{uuid}`                | `admin.candidates.delete` | Soft delete; sets `deleted_by`       |
| `POST`   | `/candidates/{uuid}/restore`        | `admin.candidates.edit`   | Restore soft-deleted                 |
| `PATCH`  | `/candidates/{uuid}/profile-status` | `admin.candidates.edit`   | `profile_status` + optional `reason` |

## Candidate profile (app routes only)

Section saves, profile read/write, and self-service publish live under **`/api/v1/app/auth/candidate/profile/*`** (authenticated candidate, own profile only). Peer profile reads use **`GET /api/v1/app/auth/candidate/{uuid}/profile-details`**.

Admin staff workflow:

1. `POST /candidates` — create with basic details.
2. Candidate completes profile via app routes (or use **impersonate** to open the member app).
3. `POST /candidates/{uuid}/publish` — staff publish when ready (optional; candidates may use `POST .../app/auth/candidate/profile/publish`).
4. `PATCH /candidates/{uuid}/featured` — feature toggle (published only).

`GET /candidates/{uuid}` — admin summary card (not the full profile-details payload).

## Impersonate (member app login)

| Method | Path                             | Permission                     |
| ------ | -------------------------------- | ------------------------------ |
| `POST` | `/candidates/{uuid}/impersonate` | `admin.candidates.impersonate` |

Returns the same shape as app auth login: `token`, `token_type`, `expires_at`, `session_token_hash`, `user`, `permissions`. Use the token against `/api/v1/app/*` routes. Token TTL: `API_IMPERSONATION_TOKEN_TTL_MINUTES` (default 60). Logged via audit + activity; `Log::warning` on each start.

## Export / import (CSV basics)

| Method | Path                            | Permission                | Notes                                                                                                                                                                          |
| ------ | ------------------------------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `GET`  | `/candidates/export`            | `admin.candidates.export` | Same query filters as index (no pagination). Max rows: `ADMIN_CANDIDATES_EXPORT_MAX_ROWS` (default 5000).                                                                      |
| `POST` | `/candidates/import`            | `admin.candidates.import` | `multipart/form-data` field `file` (`.csv`). Required column: `email`. Optional: `first_name`, `last_name`, `phone`, `gender`, `marital_status`, `profile_status`, `password`. |
| `GET`  | `/candidates/import/{importId}` | `admin.candidates.import` | Poll queued import status.                                                                                                                                                     |

Imports with ≤200 rows run synchronously (`200` + `summary`). Larger files return `202` + `import_id` and process on the low queue.

## KYC

| Method  | Path                                       | Permission              | Notes                                                                                 |
| ------- | ------------------------------------------ | ----------------------- | ------------------------------------------------------------------------------------- |
| `GET`   | `/candidates/kyc/pending`                  | `admin.candidates.view` | Global pending queue                                                                  |
| `GET`   | `/candidates/{uuid}/kyc/documents`         | `admin.candidates.view` | All documents for one candidate                                                       |
| `PATCH` | `/candidates/kyc/documents/{documentUuid}` | `admin.candidates.edit` | Verify ID: `verification_status` = `approved`, `rejected`, or `resubmission_required` |

**Profile status change:** `PATCH /candidates/{uuid}/profile-status` with `profile_status` (`draft`, `under_review`, `published`, `suspended`, `spam`).

## Observability

- **Audit**: `LogAuditJob` on create, update, delete, restore, profile-status, section saves.
- **Activity**: `LogUserActivityJob` on index and mutations.
- **Errors**: `Log::error` in service/controller; unhandled exceptions reported to **Sentry** via `app/Exceptions/Handler.php`.
- **Queues**: Algolia sync via `SyncProfileToAlgolia` on profile changes (`UserObserver`).
- **Events**: `UserCreatedEvent`, `UserLifecycleEvent` on user lifecycle (`UserObserver`).
