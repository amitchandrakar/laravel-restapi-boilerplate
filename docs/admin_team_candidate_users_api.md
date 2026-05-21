# Admin Team & Candidate Users API

This API provides split admin CRUD endpoints for Team users and Candidates on top of the shared `users` table.

## Base Prefix

- `/api/v1/admin/team-users`
- `/api/v1/admin/candidates`

All routes require `auth:sanctum` and active tracked session (except where noted in the global API docs).

## Permissions

- Team:
    - `admin.teams.view`
    - `admin.teams.add`
    - `admin.teams.edit`
    - `admin.teams.delete`
- Candidate:
    - `admin.candidates.view`
    - `admin.candidates.add`
    - `admin.candidates.edit`
    - `admin.candidates.delete`

## Team Management

### List team members

`GET /api/v1/admin/team-users`

Query parameters:

| Parameter    | Type   | Description                                    |
| ------------ | ------ | ---------------------------------------------- |
| `perPage`    | int    | Page size (1–100, default 15)                  |
| `page`       | int    | Page number                                    |
| `search`     | string | Matches first name, last name, email, or phone |
| `role_id`    | int    | Filter by role                                 |
| `status`     | string | `active`, `inactive`, `suspended`              |
| `gender`     | string | `male`, `female`, `other`                      |
| `city`       | string | Partial match on `current_city`                |
| `state`      | string | Partial match on `current_state`               |
| `country`    | string | Partial match on `current_country`             |
| `department` | string | Partial match on department                    |
| `sort`       | string | `latest` (default), `oldest`, `name`           |

### Permission options (checkbox list)

`GET /api/v1/admin/team-users/permission-options`

Returns admin-panel permissions grouped by module for the team member form.

### Create team member

`POST /api/v1/admin/team-users`

Required fields:

- `first_name`, `last_name`, `email`, `phone`, `gender`, `role_id`, `city`, `password`, `password_confirmation`

Optional fields:

- `profile_photo` (multipart image, max 5MB)
- `profile_photo_url` (external URL alternative)
- `permission_ids` (array of permission IDs — direct permissions on top of role)
- `state`, `country`, `about`, `department`, `job_title`, `status`

`role_id` must reference role `admin` or `reviewer`.

Example:

```json
{
    "first_name": "Riya",
    "last_name": "Chandrakar",
    "email": "team.member@example.com",
    "phone": "9999999999",
    "gender": "female",
    "role_id": 2,
    "permission_ids": [12, 15],
    "city": "Raipur",
    "state": "Chhattisgarh",
    "country": "India",
    "about": "Reviewer for candidate KYC workflows.",
    "status": "active",
    "password": "Password@123",
    "password_confirmation": "Password@123"
}
```

### Show / update / delete

- `GET /api/v1/admin/team-users/{uuid}`
- `PUT|PATCH /api/v1/admin/team-users/{uuid}`
- `DELETE /api/v1/admin/team-users/{uuid}`

Update accepts the same fields as create (all optional with `sometimes` rules). Send `permission_ids: []` to clear direct permissions.

### Team response shape

- `firstName`, `lastName`, `name`
- `roleId`, `role`
- `profilePhoto`
- `location`: `{ city, state, country }`
- `about`
- `permissionIds`: direct permission IDs assigned to the user
- `permissions`: effective permission names (role + direct)
- `modules`: module metadata derived from permissions

## Candidate Payload

- Reuses existing user request structure.
- New records are always persisted with candidate `role_id` (from roles table).
- Candidate role is assigned on create.

## Logging and Lifecycle Hooks

After each successful team create/update/delete:

- queued `LogAuditJob` → `audit_logs`
- queued `LogUserActivityJob` → `user_activity_logs`
- `UserObserver` → `UserLifecycleEvent` + `TeamMemberLifecycleEvent` (team members only)
- `TeamMemberLifecycleListener` → `TeamMemberNotification` (database) to other staff with `admin.teams.view`

Profile photo processing uses `TeamUserProfilePhotoService` (WebP via GD). Unhandled exceptions are reported to Sentry via the global exception handler.

## Authorization

Team routes use `TeamUserPolicy` gates (`viewAnyTeamMember`, `viewTeamMember`, `createTeamMember`, `updateTeamMember`, `deleteTeamMember`) in addition to route `permission:admin.teams.*` middleware.
