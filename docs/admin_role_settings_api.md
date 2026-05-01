# Admin Role Settings API

Manage application roles and their permission assignments (Spatie RBAC).

## Permissions

- `admin.settings.roles.view` — list roles, fetch permissions for a role
- `admin.settings.roles.edit` — create role, update role, delete role

## Endpoints

| Method | Path                                                  | Permission                  |
| ------ | ----------------------------------------------------- | --------------------------- |
| GET    | `/api/v1/admin/settings/roles`                        | `admin.settings.roles.view` |
| POST   | `/api/v1/admin/settings/roles`                        | `admin.settings.roles.edit` |
| GET    | `/api/v1/admin/settings/roles/{roleUuid}/permissions` | `admin.settings.roles.view` |
| PUT    | `/api/v1/admin/settings/roles/{roleUuid}`             | `admin.settings.roles.edit` |
| DELETE | `/api/v1/admin/settings/roles/{roleUuid}`             | `admin.settings.roles.edit` |

Auth: `Bearer` Sanctum token.

`{roleUuid}` is the role’s `uuid` column (`roles.uuid`).

Only roles with `guard_name = web` are exposed (same guard as seeders / Sanctum).

## POST create body

Creates a **custom** role (`is_system = false`, `guard_name = web`). `name` must be unique among `web` roles.

```json
{
    "name": "custom_role",
    "title": "Custom role title",
    "description": "Optional description",
    "is_default_registration": false,
    "permission_ids": [1, 2, 3]
}
```

- `name` — required string (max 255).
- `title`, `description`, `is_default_registration` — optional (same semantics as PUT).
- `permission_ids` — optional; if omitted, the role is created with no permissions. If present, permissions are synced to that set.

Success response matches PUT: `data.role` with `id`, `uuid`, `name`, `title`, `description`, `guardName`, `isSystem`, `isDefaultRegistration`.

## GET list response

`data` is an array of roles, each including:

- `id`, `uuid`, `name`, `title`, `description`, `guardName`
- `isSystem`, `isDefaultRegistration`
- `permissionCount` (number of permissions attached to the role)
- `createdAt`, `updatedAt`

## GET permissions by role

Returns:

```json
{
  "data": {
    "role": { "uuid": "...", "name": "admin", "permissionCount": 42, "..." },
    "permissions": [
      {
        "id": 1,
        "uuid": "...",
        "name": "admin.dashboard.view",
        "title": "View dashboard",
        "guardName": "web",
        "module": { "id": 1, "code": "admin_dashboard", "name": "Admin — Dashboard" }
      }
    ]
  }
}
```

## PUT update body

All fields optional (partial update):

```json
{
    "name": "custom_role",
    "title": "Custom role title",
    "description": "Optional description",
    "is_default_registration": false,
    "permission_ids": [1, 2, 3]
}
```

- `permission_ids`: replaces the role’s permissions with this set (Spatie `syncPermissions`).
- `is_default_registration`: when set to `true`, all other roles are cleared to `false` for the same guard (only one default registration role).

### System roles (`is_system = true`)

- **Cannot be deleted** (DELETE returns `422`).
- **Name cannot be changed** (PUT with a different `name` returns `422`).
- **Title**, **description**, and **permission sync** are allowed.

### Custom roles

- Full update including `name` (must remain unique per `guard_name`).
- DELETE removes the role; `role_has_permissions` and `model_has_roles` pivot rows are removed by the database. **Global `permissions` rows are not deleted.**

## DELETE response

Success envelope with `data: null` and message `Role deleted successfully`.

## Test command

```bash
php artisan test tests/Feature/AdminRoleSettingsTest.php
```
