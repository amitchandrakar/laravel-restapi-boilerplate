# Module, Role, and Permission — Complete Plan

This document is the single source of truth for implementing **modules**, **roles**, and **permissions** for the **admin site only** (admin UI at `/admin/*` and the corresponding **admin API** surface). It aligns with the MySQL data model and maps to admin routes in [`src/App.tsx`](src/App.tsx), [`src/components/admin/AdminSidebarNav.tsx`](src/components/admin/AdminSidebarNav.tsx), [`src/pages/admin/AdminSettingsLayout.tsx`](src/pages/admin/AdminSettingsLayout.tsx), and [`src/lib/admin-reports-constants.ts`](src/lib/admin-reports-constants.ts).

### Scope (important)

| In scope                                                                | Out of scope for this RBAC system                                                                                             |
| ----------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Admin panel pages and **admin** backend routes                          | **Public** pages (home, about, contact, legal, marketing)                                                                     |
| Permission checks on admin navigation and admin actions                 | **Member account** area — **not** gated by `role_permissions` (see **package features** below)                                |
| Seeding `roles` (`admin`, `reviewer`, `candidate`) for product identity | Treating **purchased package** entitlements as if they were RBAC `permissions` rows (they use a **separate model**; see §1.1) |

**Roles still exist** in the database for the whole product (`admin`, `reviewer`, `candidate`), but **only `admin` and `reviewer` receive any rows in `role_permissions` for admin modules.** The **`candidate`** role has **no** admin-module permissions (cannot open `/admin` via this system). Reviewer is the constrained staff role; admin is full access.

### 1.1 Package features (member / candidate) — separate from RBAC

Candidates see **pages and sections** based on the **package they purchased** (active **subscription** → **package**). That is **not** the same system as admin **roles / modules / permissions**:

| Concern                   | Mechanism                                                                      | Who                                    |
| ------------------------- | ------------------------------------------------------------------------------ | -------------------------------------- |
| **Admin staff access**    | `roles` + `role_permissions` + `permissions` (this document)                   | `admin`, `reviewer`                    |
| **Member feature access** | **Package entitlements** (feature flags per package) + **active subscription** | `candidate` (and any logged-in member) |

**Do not** store package feature toggles inside `role_permissions`. Use **`packages`** (and related tables such as **`subscriptions`** / **`payments`** from [`DB_DESIGNS.md`](DB_DESIGNS.md)) plus an explicit **feature matrix** on each package.

The website today encodes a fixed set of **feature keys** and a boolean vector per package in [`src/lib/packages-config.ts`](src/lib/packages-config.ts) (`PACKAGE_FEATURE_KEYS`, `includedFeatures`). For the backend, mirror that idea with stable keys, for example:

- `pricing.feature.browse_limited`
- `pricing.feature.browse_full`
- `pricing.feature.view_details`
- `pricing.feature.send_requests`
- `pricing.feature.matches`
- `pricing.feature.favorites`
- `pricing.feature.partner_prefs`
- `pricing.feature.lifestyle`
- `pricing.feature.highlighting`
- `pricing.feature.instant_contact`

**Resolver (member API):** for each request (or session bootstrap), compute `effectiveFeatureKeys` = union of flags from the user’s **current active package** (via `subscriptions` → `packages`). Member routes and UI sections check **`hasFeature('pricing.feature.matches')`** (or equivalent), **not** `hasPermission('admin.*')`.

**Optional DB shape:** `package_features` (`package_id`, `feature_key`, `enabled`) or a JSON column on `packages` (e.g. `features_json`) — choose one; normalize keys to the catalog above so admin “Packages” UI and API stay aligned.

**Admin overlap:** only **admin** (and optionally **reviewer** if you allow) edits **packages** and feature matrices in admin; that remains under admin RBAC (`admin.packages.*`), not under candidate package entitlements.

---

## 1. Goals

- **Modules:** Coarse **admin** feature areas (match admin sidebar and settings).
- **Permissions:** Atomic **admin** capabilities per module using **view**, **add**, **edit**, and **delete** where applicable. Not every module needs all four.
- **Roles:** **admin**, **reviewer**, **candidate** — only **admin** and **reviewer** are assigned `role_permissions` for admin modules; **candidate** has zero admin permissions here.
- **Stable keys:** Machine keys like `admin.candidates.view` for **admin** middleware, admin UI gates, and audit logs.
- **Package entitlements (parallel track):** persist per-package **feature keys** for members; enforce on **member/public APIs** and client section visibility — documented in §1.1 and reinforced in §7.

---

## 2. Data Model (MySQL)

### 2.1 `modules`

| Column                      | Notes                                                  |
| --------------------------- | ------------------------------------------------------ |
| `id`                        | PK                                                     |
| `code`                      | Unique snake_case identifier (e.g. `admin_candidates`) |
| `name`                      | Display name                                           |
| `description`               | Short text                                             |
| `parent_id`                 | Nullable FK → `modules.id` for hierarchy (optional v1) |
| `sort_order`                | UI ordering                                            |
| `is_active`                 | Soft-disable without deleting rows                     |
| `created_at` / `updated_at` | Timestamps                                             |

### 2.2 `permissions`

| Column                      | Notes                                                                                            |
| --------------------------- | ------------------------------------------------------------------------------------------------ |
| `id`                        | PK                                                                                               |
| `uuid`                      | Optional public identifier                                                                       |
| `module_id`                 | FK → `modules.id`                                                                                |
| `key`                       | **Unique** global key, e.g. `admin.candidates.view`                                              |
| `action`                    | One of: `view`, `add`, `edit`, `delete` (denormalized for filtering; must match suffix of `key`) |
| `name`                      | Display label                                                                                    |
| `description`               | Optional                                                                                         |
| `is_active`                 | Soft-disable                                                                                     |
| `created_at` / `updated_at` | Timestamps                                                                                       |

**Key naming convention:** `{module.code_with_dots}.{action}`

Example: module code `admin_candidates` → permission keys `admin.candidates.view`, `admin.candidates.add`, …

_(Use dots in `key` for readability; store `module_id` for joins. Module `code` in DB can be `admin_candidates` and display as “Admin — Candidates”.)_

### 2.3 `roles`

| Column                      | Notes                                                                   |
| --------------------------- | ----------------------------------------------------------------------- |
| `id`                        | PK                                                                      |
| `uuid`                      | Optional                                                                |
| `code`                      | **Unique:** `admin`, `reviewer`, `candidate` (matches auth storage key) |
| `name`                      | Display name                                                            |
| `description`               | Optional                                                                |
| `is_system`                 | `true` for these three; prevent deletion                                |
| `is_default_registration`   | `true` for `candidate` only                                             |
| `created_at` / `updated_at` | Timestamps                                                              |

### 2.4 `role_permissions`

| Column                              | Notes                 |
| ----------------------------------- | --------------------- |
| `role_id`                           | FK → `roles.id`       |
| `permission_id`                     | FK → `permissions.id` |
| Unique (`role_id`, `permission_id`) |                       |

### 2.5 `user_roles`

| Column        | Notes                               |
| ------------- | ----------------------------------- |
| `id`          | PK                                  |
| `user_id`     | FK → `users.id`                     |
| `role_id`     | FK → `roles.id`                     |
| `assigned_by` | Nullable user id                    |
| `assigned_at` | Timestamp                           |
| `is_active`   | Allow revoking without history loss |

**Note:** If a user must have only one role at a time, enforce in application layer or use a partial unique index on `(user_id)` where `is_active = 1`.

---

## 3. Actions: view, add, edit, delete

| Action     | Typical use                                                    |
| ---------- | -------------------------------------------------------------- |
| **view**   | List, read, export read-only, reports, dashboards              |
| **add**    | Create new record (candidate, package, assignment)             |
| **edit**   | Update existing record                                         |
| **delete** | Soft-delete or hard-delete (use soft-delete in product tables) |

Rules:

- Use **only the actions that apply** to a module; omit rows for impossible actions (e.g. no `add` on “reports” if reports are generated).
- **API enforcement:** each **admin** route checks required `permission.key` (often `view` for GET, `add` for POST, `edit` for PATCH, `delete` for DELETE).

---

## 4. Admin modules (catalog)

Only the following **admin** areas get `modules` rows and child `permissions`. Source: [`src/App.tsx`](src/App.tsx) admin routes, [`AdminSidebarNav.tsx`](src/components/admin/AdminSidebarNav.tsx), [`AdminSettingsLayout.tsx`](src/pages/admin/AdminSettingsLayout.tsx), [`admin-reports-constants.ts`](src/lib/admin-reports-constants.ts).

### 4.1 Admin — core

| Code               | Name                       | Routes                                     |
| ------------------ | -------------------------- | ------------------------------------------ |
| `admin_dashboard`  | Admin — Dashboard          | `/admin`                                   |
| `admin_candidates` | Admin — Candidates         | `/admin/candidates`, `/new`, `/:uuid/edit` |
| `admin_teams`      | Admin — Teams              | `/admin/teams`                             |
| `admin_packages`   | Admin — Packages           | `/admin/packages`                          |
| `admin_payments`   | Admin — Payment operations | `/admin/payments/:status`                  |

**Suggested permissions**

| Module             | view | add | edit               | delete                    |
| ------------------ | ---- | --- | ------------------ | ------------------------- |
| `admin_dashboard`  | ✓    | —   | —                  | —                         |
| `admin_candidates` | ✓    | ✓   | ✓                  | ✓ (soft-delete candidate) |
| `admin_teams`      | ✓    | ✓   | ✓                  | ✓                         |
| `admin_packages`   | ✓    | ✓   | ✓                  | ✓                         |
| `admin_payments`   | ✓    | —   | ✓ (approve/reject) | —                         |

### 4.2 Admin — reports

Report segments from [`REPORT_SEGMENT_META`](src/lib/admin-reports-constants.ts):

| Code                            | Name                              | Route segment     |
| ------------------------------- | --------------------------------- | ----------------- |
| `admin_reports_state`           | Admin — Reports (State)           | `state`           |
| `admin_reports_community`       | Admin — Reports (Community)       | `community`       |
| `admin_reports_education`       | Admin — Reports (Education)       | `education`       |
| `admin_reports_active_users`    | Admin — Reports (Active users)    | `active-users`    |
| `admin_reports_user_activities` | Admin — Reports (User activities) | `user-activities` |
| `admin_reports_team_activities` | Admin — Reports (Team activities) | `team-activities` |

**Suggested permissions:** **view** only for each (`admin.reports.state.view`, …) unless you add scheduled report **add**.

### 4.3 Admin — settings

| Code                      | Name                        | Routes                         |
| ------------------------- | --------------------------- | ------------------------------ |
| `admin_settings_site`     | Admin — Site / branding     | `/admin/settings/site`         |
| `admin_settings_payments` | Admin — Payment settings    | `/admin/settings/payments`     |
| `admin_settings_social`   | Admin — Social login        | `/admin/settings/social-login` |
| `admin_settings_roles`    | Admin — Roles & permissions | `/admin/settings/roles`        |
| `admin_settings_seo`      | Admin — SEO                 | `/admin/settings/seo`          |

**Suggested permissions**

| Module                  | view | add | edit | delete |
| ----------------------- | ---- | --- | ---- | ------ |
| Each settings submodule | ✓    | —   | ✓    | —      |

`admin_settings_roles` may also need **add/delete** for custom roles in future; for three fixed roles, **edit** is enough to change permission assignments.

---

## 5. Flat permission key catalog (admin only — seed reference)

Generate one row per key below in `permissions` (each linked to the correct `module_id`). **Do not** seed public or member keys from this document.

- `admin.dashboard.view`
- `admin.candidates.view`, `admin.candidates.add`, `admin.candidates.edit`, `admin.candidates.delete`
- `admin.teams.view`, `admin.teams.add`, `admin.teams.edit`, `admin.teams.delete`
- `admin.packages.view`, `admin.packages.add`, `admin.packages.edit`, `admin.packages.delete`
- `admin.payments.view`, `admin.payments.edit`
- `admin.reports.state.view`, `admin.reports.community.view`, `admin.reports.education.view`, `admin.reports.active_users.view`, `admin.reports.user_activities.view`, `admin.reports.team_activities.view`
- `admin.settings.site.view`, `admin.settings.site.edit`
- `admin.settings.payments.view`, `admin.settings.payments.edit`
- `admin.settings.social.view`, `admin.settings.social.edit`
- `admin.settings.roles.view`, `admin.settings.roles.edit` (+ add/delete if custom roles)
- `admin.settings.seo.view`, `admin.settings.seo.edit`

---

## 6. Role → permission matrix (admin keys only)

Legend: **Y** = grant that permission key (or the whole family where noted); **—** = no grant.

Only keys listed in **section 5** apply.

### 6.1 `admin`

- **Y** on **all** keys in section 5 (every admin `view` / `add` / `edit` / `delete` that you seeded).

### 6.2 `reviewer`

Typical staff reviewer: work candidates and read operations; **no** role/permission management; limited settings.

| Permission family           | view                         | add | edit | delete                                              |
| --------------------------- | ---------------------------- | --- | ---- | --------------------------------------------------- |
| `admin.dashboard.*`         | Y                            | —   | —    | —                                                   |
| `admin.candidates.*`        | Y                            | Y   | Y    | **—** (optional Y for soft-delete if policy allows) |
| `admin.teams.*`             | Y                            | —   | —    | —                                                   |
| `admin.packages.*`          | Y                            | —   | —    | —                                                   |
| `admin.payments.*`          | Y                            | —   | Y    | —                                                   |
| `admin.reports.*`           | Y (all report segment views) | —   | —    | —                                                   |
| `admin.settings.site.*`     | Y                            | —   | —    | —                                                   |
| `admin.settings.payments.*` | Y                            | —   | —    | —                                                   |
| `admin.settings.social.*`   | Y                            | —   | —    | —                                                   |
| `admin.settings.roles.*`    | **—**                        | —   | —    | —                                                   |
| `admin.settings.seo.*`      | Y                            | —   | —    | —                                                   |

_(If reviewers should edit site copy, grant `admin.settings.site.edit` explicitly.)_

### 6.3 `candidate`

- **No** grants from section 5 (`role_permissions` has **no rows** for `candidate` on admin modules).
- **Public and member** access: use **login/session**, **resource ownership**, and **package feature entitlements** (§1.1) — not admin `permissions`.
- If the website today uses `member.browse` / `member.account` in localStorage for UI only, align long-term with **subscription + package feature flags** from the backend; admin RBAC remains unrelated.

---

## 7. Implementation plan (execution checklist)

1. **Migrations:** create `modules`, extend `permissions` with `module_id` + `action`, `role_permissions`, ensure `roles` + `user_roles` match [`DB_DESIGNS.md`](DB_DESIGNS.md).
2. **Seed:** insert **admin modules only** (section 4), then **admin permission keys only** (section 5), then roles `admin`, `reviewer`, `candidate`, then `role_permissions` **only for `admin` and `reviewer`** per section 6 (`candidate` gets no admin permission rows).
3. **Resolver (admin):** e.g. `GET /api/v1/admin/me/permissions` or include admin keys in a unified `/me` payload — returns `{ keys: string[] }` for the current user **restricted to admin permission keys** for use by [`AdminRoute`](src/components/AdminRoute.tsx) / layout.
4. **Middleware:** on **`/api/v1/admin/*`** (or equivalent prefix), attach resolved keys and `require('admin.candidates.view')` (etc.). **Do not** require these keys on public or member-only routes.
5. **Website admin:** sync [`src/lib/roles-permissions-config.ts`](src/lib/roles-permissions-config.ts) (or replace) so **admin panel** gates use API-returned admin keys; **`candidate`** should resolve to **empty admin key set** so `/admin` stays blocked unless you intentionally add a dev exception.
6. **Admin UI:** [`AdminRolesPermissions.tsx`](src/pages/admin/AdminRolesPermissions.tsx) loads **admin** modules + permissions; matrix editing updates `role_permissions` for **reviewer** (and optionally **admin** if you allow superuser tuning); **`candidate`** row shows no assignable admin permissions (or hide row).
7. **Docs:** when new **admin** routes ship, add module/permission rows here and in seed SQL; changelog per release.
8. **Package entitlements (member):** implement `effectiveFeatureKeys` (or equivalent) from `subscriptions` → `packages`; add middleware or service helper **`requirePackageFeature('pricing.feature.matches')`** on member-only routes; keep feature key catalog in sync with admin package editor and with [`PACKAGE_FEATURE_KEYS`](src/lib/packages-config.ts) (or a superset if the API adds keys). When a section is gated in the UI, use the same key the API enforces.

---

## 8. Notes

- **Gate `/admin`:** require at least one admin permission (commonly `admin.dashboard.view`) for layout access; **reviewer** must have a non-empty subset; **candidate** has none so admin UI remains closed.
- **Gate member pages/sections:** use **subscription + package features**, not `role_permissions`. Example: “Matches” tab requires `pricing.feature.matches`; downgrade or expiry clears the flag and API returns 403 or empty payload consistently.
- **Naming:** keep admin RBAC keys under `admin.*` and entitlement keys under a **non-`admin` prefix** (e.g. `pricing.feature.*`) so logs and middleware never conflate the two.
- **Single role per user** simplifies UI; multi-role is supported by the `user_roles` shape if you union permissions in the resolver.
- **Legacy demo keys** in [`roles-permissions-config.ts`](src/lib/roles-permissions-config.ts) — map **only admin-related** keys to this catalog:
    - `admin.access_panel` → `admin.dashboard.view` (or minimum set of `admin.*.view`)
    - `review.profiles` → `admin.candidates.view` + `admin.candidates.edit` (+ `add` if reviewers create profiles)
    - **`member.account`** and **`member.browse`** are **not** represented here; handle on the **public/member** app with auth + ownership, or a separate policy later.

---

_End of `module_role_permission.md`._
