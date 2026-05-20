# API permissions and not-found responses (v1)

Base path: `/api/v1`. All JSON responses use the shared envelope (`success`, `data`, `message`, `meta`, and on errors `error` with `code`, `message`, `details`).

## Error codes (machine-readable)

| Code               | HTTP | Typical cause                                                |
| ------------------ | ---- | ------------------------------------------------------------ |
| `NOT_FOUND`        | 404  | Unknown route, or missing model (by id/uuid binding)         |
| `FORBIDDEN`        | 403  | Missing Spatie permission (middleware or controller `can()`) |
| `UNAUTHORIZED`     | 401  | Missing or invalid auth                                      |
| `VALIDATION_ERROR` | 422  | Request validation failed                                    |

## Routes with path `id` or `uuid`

| Area             | Path                                                   | Key            |
| ---------------- | ------------------------------------------------------ | -------------- |
| Users API        | `/users/{user}`                                        | numeric **id** |
| Admin packages   | `/admin/packages/{package}`                            | numeric **id** |
| Admin payments   | `/admin/payments/{payment:uuid}`                       | **uuid**       |
| Admin roles      | `/admin/settings/roles/{role:uuid}` (+ `/permissions`) | **uuid**       |
| Admin team users | `/admin/team-users/{user:uuid}`                        | **uuid**       |
| Admin candidates | `/admin/candidates/{user:uuid}` …                      | **uuid**       |

When a bound model does not exist, Laravel wraps `ModelNotFoundException` in `NotFoundHttpException`; **`App\Exceptions\Handler`** (registered in [`app/Providers/AppServiceProvider.php`](../app/Providers/AppServiceProvider.php)) returns **404** with `error.code` = `NOT_FOUND` and a resource-specific message where the model type is mapped (Payment, Role, Package, User).

Domain-specific 404 messages (e.g. candidate UUID that exists but is not a candidate) are returned from the relevant controller as `Team user not found`, `Candidate not found`, etc.

## Permissions (admin)

Admin routes under `/api/v1/admin/*` use Spatie `permission:...` middleware. Controllers may repeat `$request->user()->can(...)` for parity with other admin modules.

**Users resource** (`/api/v1/admin/users/*`): requires `admin.users.view` | `admin.users.add` | `admin.users.edit` | `admin.users.delete` per action (see [`routes/api/v1/admin.php`](../routes/api/v1/admin.php)).

## Tests

See `tests/Feature/ApiPermissionsAndNotFoundTest.php` for examples of 404 and 403 behavior.
