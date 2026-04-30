# Admin Reports API

This document describes the admin report endpoints.

## Base

- Prefix: `GET /api/v1/admin/reports/*`
- Auth: `Bearer` Sanctum token required.

## Endpoints

- `GET /api/v1/admin/reports/candidates/area` (permission: `admin.reports.state.view`)
- `GET /api/v1/admin/reports/candidates/surname` (permission: `admin.reports.community.view`)
- `GET /api/v1/admin/reports/candidates/education` (permission: `admin.reports.education.view`)
- `GET /api/v1/admin/reports/active-users` (permission: `admin.reports.active_users.view`)
- `GET /api/v1/admin/reports/user-activities` (permission: `admin.reports.user_activities.view`)
- `GET /api/v1/admin/reports/team-activities` (permission: `admin.reports.team_activities.view`)

## Candidates by area

### Query params

- `groupBy`: `state|district|city|village` (default: `district`)
- `limit`: integer (default: `50`)

### Example

`GET /api/v1/admin/reports/candidates/area?groupBy=district&limit=20`

### Response (shape)

```json
{
    "success": true,
    "message": "Candidate area report fetched successfully",
    "data": {
        "groupBy": "district",
        "totalCandidates": 120,
        "buckets": [
            { "area": "Durg", "total": 35 },
            { "area": "Raipur", "total": 20 }
        ]
    }
}
```

## Candidates by surname

### Query params

- `limit`: integer (default: `50`)

### Example

`GET /api/v1/admin/reports/candidates/surname?limit=25`

## Candidates by education

### Query params

- `limit`: integer (default: `50`)

### Example

`GET /api/v1/admin/reports/candidates/education?limit=25`

## Active users (moderation)

### Query params

- `from`: optional datetime/date
- `to`: optional datetime/date
- `perPage`: integer (default: `15`)

Default behavior is all-time when `from` and `to` are not supplied.

### Example

`GET /api/v1/admin/reports/active-users?from=2026-04-01&to=2026-04-30&perPage=20`

## User activities

### Query params

- `userId`: optional numeric user id
- `activityType`: optional string
- `from`: optional datetime/date
- `to`: optional datetime/date
- `perPage`: integer (default: `15`)

Default behavior is all-time when `from` and `to` are not supplied.

### Example

`GET /api/v1/admin/reports/user-activities?activityType=auth.login&perPage=25`

## Team activities

### Query params

- `actorUserId`: optional numeric user id
- `action`: optional string
- `from`: optional datetime/date
- `to`: optional datetime/date
- `perPage`: integer (default: `15`)

Default behavior is all-time when `from` and `to` are not supplied.

### Example

`GET /api/v1/admin/reports/team-activities?action=update&perPage=25`

## Notes

- Aggregate reports (`area`, `surname`, `education`) return grouped buckets.
- Activity reports (`active-users`, `user-activities`, `team-activities`) return paginated lists with pagination in `meta.pagination`.
- Candidate role filtering is applied to candidate-based reports.

## Test command

```bash
php artisan test tests/Feature/AdminReportsTest.php
```

