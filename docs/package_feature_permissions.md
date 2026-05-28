# Candidate Package Feature Permissions

This document defines how candidate package permissions are managed and synchronized.

## Dynamic Package Mapping

Package-feature permissions are now stored dynamically in `package_permissions` (`package_id`, `permission_id`).

Admin selects permissions from candidate permission options while creating/updating a package.

Default package mappings are seeded by `PackageCatalogSeeder`, but they can be changed anytime from admin package CRUD.

## Default seeded package matrix

| Package code    | Permissions                                                                                                                                                                                                                                                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PARICHAY_FREE` | `candidate.browse_profiles.limited`                                                                                                                                                                                                                                                                                             |
| `TALASH_BASIC`  | `candidate.browse_profiles.full`, `candidate.view_full_profile_details`, `candidate.send_contact_requests`, `candidate.mark_profiles_favorite`, `candidate.view_partner_preferences_details`, `candidate.view_lifestyle_details`                                                                                                |
| `RISHTA_PRO`    | All Talash permissions, plus `candidate.browse_profiles.limited`, `candidate.view_my_matches`, `candidate.view_profile_highlighting`, `candidate.view_instant_contact_access`, `candidate.view_contact_details`, and Kundali: `candidate.generate_kundali`, `candidate.view_kundali`, `candidate.view_kundali_matching_results` |

Demo accounts: `candidate.parichay@example.com` (Parichay), `candidate.talash@example.com` (Talash), `candidate.rishta@example.com` (Rishta). Password: see `config/postman.php` / demo seeders.

Login and `GET /api/v1/auth/me` return synced permissions in `data.permissions` (package entitlements only for candidates; not admin RBAC keys).

## Candidate Permission Catalog

Selectable permissions are loaded from the permissions table where `module_id` IS NULL (package features catalog). In this project those rows use `candidate.*` permission names but are not tied to an admin module.

## Sync Lifecycle

Permissions are synchronized as direct user permissions for users with role `candidate`.

Synchronization is triggered:

- on registration, after default package subscription is attached
- on demo auth seed, after candidate subscriptions are inserted
- on subscription `created`, `updated`, and `deleted` events via `SubscriptionObserver`

The sync process:

1. Resolve the candidate's latest active subscription package.
2. Load package permissions from `package_permissions`.
3. Remove existing package-scoped candidate permissions from user direct permissions.
4. Attach current package permissions as user direct permissions.

If no active subscription exists, package-scoped candidate permissions are removed.
