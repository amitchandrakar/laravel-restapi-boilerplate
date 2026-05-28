# Shared authentication API (`/api/v1/auth/*`)

Canonical session endpoints for **candidates** and **staff**. Use these paths from the public website, admin panel, and mobile app instead of the legacy `/api/v1/app/auth/*` or `/api/v1/admin/auth/*` duplicates.

## Base path

`/api/v1/auth`

## Endpoints

| Method | Path              | Auth                     | Description                                                                |
| ------ | ----------------- | ------------------------ | -------------------------------------------------------------------------- |
| POST   | `login`           | Public (throttled)       | Issue Bearer token                                                         |
| POST   | `forgot-password` | Public (throttled)       | Request password reset email                                               |
| POST   | `reset-password`  | Public or Bearer         | Anonymous reset (email + token) or authenticated change (current password) |
| POST   | `logout`          | Bearer + tracked session | Revoke current token                                                       |
| POST   | `refresh`         | Bearer + tracked session | Rotate token                                                               |
| GET    | `me`              | Bearer + tracked session | Current user, permissions, `userType`                                      |
| PATCH  | `profile`         | Bearer + tracked session | Update name / phone                                                        |
| POST   | `change-password` | Bearer + tracked session | Change password (authenticated)                                            |

## `userType`

Returned on login (`data.userType`, `data.user.userType`) and on `GET me` (`data.userType`, `data.user.userType`):

| Value       | Meaning                       |
| ----------- | ----------------------------- |
| `candidate` | User has the `candidate` role |
| `team`      | Staff (admin, reviewer, etc.) |

## Login response (`POST login`)

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "uuid": "...", "userType": "candidate", ... },
    "userType": "candidate",
    "token": "...",
    "token_type": "Bearer",
    "permissions": ["..."]
  }
}
```

## Me response (`GET me`)

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "uuid": "...", "userType": "team", ... },
    "userType": "team",
    "permissions": ["admin.dashboard.view", "..."]
  }
}
```

## App-only auth (unchanged)

Registration, candidate profile, discovery, notifications, and payment registration remain under `/api/v1/app/auth/*`:

- `GET registration`, `POST register`, `POST register-candidate`
- `POST payment/registration/confirm`, `GET payment/registration/{uuid}/status`
- `auth/notifications/*`, `auth/candidate/*`

## Migration from legacy paths

| Old                             | New                       |
| ------------------------------- | ------------------------- |
| `POST /api/v1/app/auth/login`   | `POST /api/v1/auth/login` |
| `GET /api/v1/app/auth/me`       | `GET /api/v1/auth/me`     |
| `POST /api/v1/admin/auth/login` | `POST /api/v1/auth/login` |

Regenerate Postman: `php artisan postman:generate`.
