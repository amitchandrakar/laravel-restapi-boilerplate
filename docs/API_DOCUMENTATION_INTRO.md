# Alonti API — Documentation Introduction

## What this project is

**Alonti API** is a **versioned REST backend** for a matrimonial / community matchmaking product. It powers **member (candidate) journeys**—registration, rich profile completion, publishing—and **staff operations** through an admin-style surface: managing candidates, team users, packages, payments, reports, and site configuration. The API is built on **Laravel** (PHP 8.2+) with a focus on **clear contracts**, **predictable JSON**, and **security-by-default**.

The codebase evolved from a disciplined Laravel API foundation: **strict typing**, a **service layer** for business rules, **versioned routes**, and **test-backed** changes. Feature documentation for specific areas lives under the [`docs/`](.) directory (admin modules, candidate flows, packages, RBAC, database design, and more).

---

## API versioning

All product endpoints are namespaced by version so clients can adopt changes safely.

- **Current base path:** `/api/v1/…`
- Routes are defined in [`routes/api.php`](../routes/api.php) (API prefix), [`routes/api/v1.php`](../routes/api/v1.php) (v1 loader), [`routes/api/v1/admin.php`](../routes/api/v1/admin.php) (admin panel), and [`routes/api/v1/app.php`](../routes/api/v1/app.php) (mobile app).
- **Audience prefixes:** `/api/v1/admin/…` (staff dashboard) and `/api/v1/app/…` (member app). Shared session endpoints (`login`, `logout`, `me`, `forgot-password`, `reset-password`, etc.) live under **`/api/v1/auth/…`** — see [`docs/shared_auth_api.md`](shared_auth_api.md). Candidate signup uses **`POST /api/v1/app/auth/register`** or **`register-candidate`**; staff CRUD uses admin routes.
- Future versions (e.g. `/api/v2/…`) can be added alongside v1 without breaking existing clients.

**Example**

- `GET /api/v1/health` — service health (unversioned root may also expose utilities; v1 is the primary contract for product APIs).

---

## Core concepts and features

### Authentication (Laravel Sanctum)

- **Token-based API authentication** for protected routes using **Bearer tokens** issued after login or registration.
- Public endpoints (e.g. registration options, health, selected marketing-safe lists) do not require a token unless documented otherwise.
- Send: `Authorization: Bearer <access_token>` for Sanctum-protected requests.
- **Password reset:** forgot-password email + token reset, or reset while logged in with current password — see [`password_reset_api.md`](password_reset_api.md).

### Roles vs package entitlements (two different systems)

1. **Admin RBAC (Spatie Permission)**
    - **Roles** such as `admin`, `reviewer`, and `candidate` identify who someone is in the product.
    - **Admin and reviewer** receive granular **permissions** (e.g. `admin.candidates.view`, `admin.packages.edit`) used as **route middleware** on `/api/v1/admin/…` routes.
    - **Candidates** do not receive admin-module permissions; they use member APIs under `/api/v1/app/auth/…` (and similar) after authentication.

2. **Package / subscription features (candidates)**
    - What a **candidate** can do in the product (e.g. browse profiles, full vs limited view) is driven by **active subscription → package → feature permissions**, not by admin RBAC keys.
    - This keeps **staff access** (who can operate the back office) separate from **commercial entitlements** (what a paying member can access).

See [`module_role_permission.md`](module_role_permission.md) and [`package_feature_permissions.md`](package_feature_permissions.md) for the full model.

### Candidate profiles (sectional, draft → published)

- Profiles are built **in sections** (basics, photos, personal details, horoscope, location, career, family, lifestyle, partner preferences).
- **Draft vs published:** completion and publish rules are enforced in the **service layer**; published profiles unlock downstream behaviour (e.g. featuring on the public homepage).
- Documented in [`candidate_sectional_draft_api.md`](candidate_sectional_draft_api.md).

### Contact number requests (candidate ↔ candidate)

- Members may **request** another candidate’s **phone** via **`POST /api/v1/app/auth/candidate/contact-requests`**; the recipient **accepts or rejects** with **`PATCH …/contact-requests/{uuid}`**. Phone on **`GET /api/v1/admin/candidates/{uuid}/profile-details`** is **hidden for peers** until the request is **accepted** (staff with `admin.candidates.view` still see full contact). See [`candidate_contact_requests_api.md`](candidate_contact_requests_api.md).

### In-app notifications (member feed)

- **`GET /api/v1/app/auth/notifications`** (and read/summary helpers) expose a **kind-filtered** feed with **`actions`** for clients. See [`member_notifications_api.md`](member_notifications_api.md) and the design notes in [`notifications_plan.md`](notifications_plan.md).

### Registration and identity

- **Public registration** flows expose structured options (e.g. packages, surnames) and support **candidate registration** with validated payloads.
- **Paid packages (Razorpay UPI):** registration may return a **`payment`** block (order + key) for Checkout; confirm + webhook complete activation — see [`payment_razorpay_api.md`](payment_razorpay_api.md).
- **KYC (Know Your Customer):** candidates submit **identity document metadata** (e.g. Aadhaar, driving licence) as URLs and masked identifiers; **admin/reviewer** workflows move documents through **pending → approved / rejected / resubmission_required** with audit-friendly timestamps.
- **Featured profiles:** admins (with the dedicated permission) may mark **published** candidates as featured; a **public, rate-limited** endpoint returns a **minimal, non-sensitive** teaser for marketing or discovery UIs.

### Admin domain modules

Typical admin surfaces exposed over the API include (non-exhaustive): **candidates**, **team users**, **packages** (including permission matrices), **payments**, **reports** (aggregates and activity), **dashboard stats**, and **settings** (site, SEO, social login, roles). Each area uses **Form Request** validation, **JSON resources** for stable response shapes, and **permission middleware** aligned with seeded roles.

### Master data and geography

- Reference data (e.g. regions, education, occupations) is seeded and documented where applicable (e.g. [`chhattisgarh_master_data.md`](chhattisgarh_master_data.md)) so clients can build consistent UIs against stable identifiers.

### Observability and compliance hooks

- **Queued jobs** and **audit / activity logging** are used for important actions (patterns vary by endpoint; see implementation and tests).
- **Verification and compliance** tables (e.g. identity documents, account lifecycle) support product and regulatory workflows over time.

---

## Technical practices in this codebase

| Practice                        | How it shows up in the API                                                                                                                                                                                             |
| ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **REST-style routing**          | Resource-oriented URLs under `/api/v1`, meaningful HTTP verbs, and consistent use of UUIDs where exposed externally.                                                                                                   |
| **Form requests**               | `App\Http\Requests\Api\V1\…` classes centralise validation rules and error shaping for invalid input (typically **422** with field errors).                                                                            |
| **API resources**               | `App\Http\Resources\Api\V1\…` map internal models to **stable, camelCase JSON** for clients and reduce accidental leakage of internal fields.                                                                          |
| **Service layer**               | `App\Services\…` holds business rules, transactions, and orchestration so controllers stay thin and testable.                                                                                                          |
| **Standardised JSON envelopes** | Success and error responses use a shared builder (`ApiResponseBuilder`) with **machine-readable error codes**, **meta** (e.g. request id, timestamp, API version), and **pagination** blocks where lists are pageable. |
| **Strict types**                | `declare(strict_types=1);` is used across PHP units to catch type errors early.                                                                                                                                        |
| **Testing**                     | Feature tests under `tests/Feature` exercise HTTP contracts, permissions, and critical flows; new behaviour is expected to ship with tests.                                                                            |
| **Static analysis & style**     | Larastan / PHPStan and Laravel Pint support consistent quality (see project tooling in `README.md`).                                                                                                                   |

---

## Laravel building blocks used in this project

- **Migrations**: Versioned schema changes live in `database/migrations` (core tables, profile extensions, compliance/KYC, featured flags, and more).
- **Seeders**: Repeatable data bootstrapping in `database/seeders` (RBAC roles/permissions, package catalog, demo/master data).
- **Models**: Eloquent models in `app/Models` define casts, relations, scopes, and UUID-centric lookup patterns.
- **Controllers**: Versioned HTTP entrypoints in `app/Http/Controllers/Api/V1` keep request/response orchestration thin.
- **Form Requests**: Validation and request authorization contracts in `app/Http/Requests/Api/V1`.
- **Services**: Domain/business logic in `app/Services` (registration, candidate profile sections, KYC review, featured candidates, payments, reports).
- **Observers**: Model lifecycle hooks in `app/Observers` for cross-cutting synchronization and side effects.
- **Events / Listeners**: Event-driven decoupling for lifecycle actions and follow-up processing.
- **Notifications**: Domain notifications for important system/user events.
- **Queues / Jobs**: Asynchronous processing in `app/Jobs` for audit/activity logging and heavier background tasks.
- **API Resources**: Stable, client-safe transformation layer in `app/Http/Resources/Api/V1`.

---

## Error handling and API contract strategy

- **Unified envelope**: Responses are standardized via `ApiResponseBuilder` + `ApiResponse` trait.
- **Validation errors**: Form Requests return consistent `422` payloads with field-level errors.
- **Auth / permission errors**: Consistent `401`/`403` responses for token and RBAC failures.
- **Not found / fallback**: Unknown routes and missing resources return normalized error shapes (`404`).
- **Operational metadata**: Response `meta` includes request identifiers and timestamps to help traceability.

---

## Tooling, quality gates, and developer workflow

- **Project setup script**: `scripts/setup-project.sh` automates local bootstrap steps.
- **Git hooks**: Pre-commit hooks are installed to run checks before code is committed.
- **Code style**: Laravel Pint is used for formatting and coding standards enforcement.
- **Static analysis**: Larastan/PHPStan (`phpstan.neon`) enforces type-safe architecture.
- **Tests**: PHPUnit feature tests validate real HTTP behaviour, permissions, and regressions.
- **Documentation-first practice**: `docs/` includes module-level API docs, RBAC design, and database references.

---

## Security, compliance, and scalability strategies

- **Security-by-default middleware**: Sanctum token auth, permission middleware, and API-focused middleware stack.
- **Least privilege access model**: Fine-grained role/permission keys for admin operations and separate member entitlements via packages.
- **Versioned APIs**: `/api/v1` boundary enables non-breaking evolution as the product grows.
- **Service-layer domain boundaries**: Business rules centralized in services reduce controller complexity and improve maintainability.
- **Asynchronous workloads**: Queued jobs keep request latency predictable and improve horizontal scalability.
- **Compliance-oriented data model**: Verification/compliance tables support auditable KYC and account lifecycle workflows.
- **Observability and traceability**: Audit/activity logging and consistent response metadata improve incident analysis and operations.

---

## Using this documentation (and Postman)

1. **Environment** — Set **base URL** (e.g. `https://your-host.example` or `http://localhost:8000`) and **Bearer token** variables for authenticated folders.
2. **Version** — Prefix paths with **`/api/v1`** unless a route is explicitly documented at another root.
3. **Errors** — Interpret **HTTP status** (`401`, `403`, `404`, `422`, `429`, `5xx`) together with the JSON **`error.code`** and optional **`errors`** / **`fields`** payload for validation.
4. **Deep dives** — Use the linked markdown files in `docs/` for payloads, edge cases, and permission names per module.

This introduction is meant to sit at the top of the API reference or Postman collection description so readers understand **what Alonti API is**, **which Laravel capabilities and packages are used**, **how auth/permissions/error handling work**, and **which scalability/security/compliance strategies** shape the implementation.
