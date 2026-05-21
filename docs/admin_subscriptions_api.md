# Admin Subscriptions API

Read-only endpoints for subscription management views. All routes require `auth:sanctum` and `admin.subscriptions.view`.

## Endpoints

| Method | Path                                              | Description                               |
| ------ | ------------------------------------------------- | ----------------------------------------- |
| GET    | `/api/v1/admin/subscriptions/active`              | Active subscriptions                      |
| GET    | `/api/v1/admin/subscriptions/expiring-soon`       | Active subscriptions ending within 7 days |
| GET    | `/api/v1/admin/subscriptions/expired`             | Expired subscriptions                     |
| GET    | `/api/v1/admin/subscriptions/history/{user:uuid}` | All subscription rows for a candidate     |

## Query parameters (list endpoints)

| Parameter    | Type   | Description                     |
| ------------ | ------ | ------------------------------- |
| `perPage`    | int    | 1–100 (default 15)              |
| `page`       | int    | Page number                     |
| `search`     | string | Candidate name, email, or phone |
| `package_id` | int    | Filter by package               |
| `ends_from`  | date   | Minimum `ends_at`               |
| `ends_to`    | date   | Maximum `ends_at`               |

## Response item shape

Each row includes:

- Subscription: `subscriptionUuid`, `subscriptionStatus`, `startedAt`, `endsAt`
- Candidate: `uuid`, `fullName`, `profilePhoto`, `email`
- Package: `name`, `code`, `price` (display price by `durationUnit`), `currency`, `durationUnit`

## Permissions

- `admin.subscriptions.view` — required for all endpoints (reviewer role includes view-only access)
