# Alonti API — Documentation Introduction

## What this project is

**Alonti API** is a **versioned REST backend** for a matrimonial / community matchmaking product. It powers **member (candidate) journeys**—registration, rich profile completion, publishing—and **staff operations** through an admin-style surface: managing candidates, team users, packages, payments, reports, and site configuration. The API is built on **Laravel** (PHP 8.2+) with a focus on **clear contracts**, **predictable JSON**, and **security-by-default**.

The codebase evolved from a disciplined Laravel API foundation: **strict typing**, a **service layer** for business rules, **versioned routes**, and **test-backed** changes. Feature documentation for specific areas lives under the [`docs/`](.) directory (admin modules, candidate flows, packages, RBAC, database design, and more).

---

## API versioning

All product endpoints are namespaced by version so clients can adopt changes safely.

- **Current base path:** `/api/v1/…`
- Routes are defined in [`routes/api.php`](../routes/api.php) (API prefix) and [`routes/api/v1.php`](../routes/api/v1.php) (v1 group).
- Future versions (e.g. `/api/v2/…`) can be added alongside v1 without breaking existing clients.

**Example**

- `GET /api/v1/health` — service health (unversioned root may also expose utilities; v1 is the primary contract for product APIs).

---

## Core concepts and features

### Authentication (Laravel Sanctum)

- **Token-based API authentication** for protected routes using **Bearer tokens** issued after login or registration.
- Public endpoints (e.g. registration options, health, selected marketing-safe lists) do not require a token unless documented otherwise.
- Send: `Authorization: Bearer <access_token>` for Sanctum-protected requests.

### Roles vs package entitlements (two different systems)

1. **Admin RBAC (Spatie Permission)**
    - **Roles** such as `admin`, `reviewer`, and `candidate` identify who someone is in the product.
    - **Admin and reviewer** receive granular **permissions** (e.g. `admin.candidates.view`, `admin.packages.edit`) used as **route middleware** on `/api/v1/admin/…` routes.
    - **Candidates** do not receive admin-module permissions; they use member APIs under `/api/v1/auth/…` (and similar) after authentication.

2. **Package / subscription features (candidates)**
    - What a **candidate** can do in the product (e.g. browse profiles, full vs limited view) is driven by **active subscription → package → feature permissions**, not by admin RBAC keys.
    - This keeps **staff access** (who can operate the back office) separate from **commercial entitlements** (what a paying member can access).

See [`module_role_permission.md`](module_role_permission.md) and [`package_feature_permissions.md`](package_feature_permissions.md) for the full model.

### Candidate profiles (sectional, draft → published)

- Profiles are built **in sections** (basics, photos, personal details, horoscope, location, career, family, lifestyle, partner preferences).
- **Draft vs published:** completion and publish rules are enforced in the **service layer**; published profiles unlock downstream behaviour (e.g. featuring on the public homepage).
- Documented in [`candidate_sectional_draft_api.md`](candidate_sectional_draft_api.md).

### Registration and identity

- **Public registration** flows expose structured options (e.g. packages, surnames) and support **candidate registration** with validated payloads.
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

## Using this documentation (and Postman)

1. **Environment** — Set **base URL** (e.g. `https://your-host.example` or `http://localhost:8000`) and **Bearer token** variables for authenticated folders.
2. **Version** — Prefix paths with **`/api/v1`** unless a route is explicitly documented at another root.
3. **Errors** — Interpret **HTTP status** (`401`, `403`, `404`, `422`, `429`, `5xx`) together with the JSON **`error.code`** and optional **`errors`** / **`fields`** payload for validation.
4. **Deep dives** — Use the linked markdown files in `docs/` for payloads, edge cases, and permission names per module.

This introduction is meant to sit at the top of the API reference or Postman collection description so readers understand **what Alonti API is**, **how auth and authorisation work**, and **which engineering conventions** to expect before opening individual endpoint chapters.
