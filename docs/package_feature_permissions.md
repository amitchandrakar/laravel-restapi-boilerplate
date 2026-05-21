# Candidate Package Feature Permissions

This document defines how candidate package permissions are managed and synchronized.

## Dynamic Package Mapping

Package-feature permissions are now stored dynamically in `package_permissions` (`package_id`, `permission_id`).

Admin selects permissions from candidate permission options while creating/updating a package.

Default package mappings are seeded by `PackageCatalogSeeder`, but they can be changed anytime from admin package CRUD.

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
