# Admin Team & Candidate Users API

This API provides split admin CRUD endpoints for Team users and Candidates on top of the shared `users` table.

## Base Prefix

- `/api/v1/admin/team-users`
- `/api/v1/admin/candidates`

All routes require `auth:sanctum`.

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

## Team User Payload

- Required create fields:
    - `name`, `email`, `phone`, `gender`, `role_id`, `department`, `job_title`, `city`, `password`, `password_confirmation`
- Optional:
    - `profile_photo_url`, `status`
- `role_id` must belong to a team-assignable role: `admin` or `reviewer`.

Example create request:

```json
{
    "name": "Team Member",
    "email": "team.member@example.com",
    "phone": "9999999999",
    "gender": "male",
    "role_id": 2,
    "department": "Operations",
    "job_title": "Lead",
    "city": "Raipur",
    "status": "active",
    "password": "Password@123",
    "password_confirmation": "Password@123"
}
```

## Candidate Payload

- Reuses existing user request structure.
- New records are always persisted with candidate `role_id` (from roles table).
- Candidate role is assigned on create.

## Logging and Lifecycle Hooks

After each successful create/update/delete:

- queued `LogAuditJob` is dispatched for `audit_logs`
- queued `LogUserActivityJob` is dispatched for `user_activity_logs`

Additionally, `UserObserver` dispatches `UserLifecycleEvent` on created/updated/deleted, and `UserLifecycleListener` sends database notifications to admins.

## Team Response Access Snapshot

Team user responses include:

- `roleId` and `role`
- `permissions`: resolved effective permission names via assigned role
- `modules`: unique module metadata inferred from those permissions

This guarantees admin/reviewer role assignments reflect the expected access surface for UI verification.
