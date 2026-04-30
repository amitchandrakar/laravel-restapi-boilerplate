# Admin Packages API

This document describes complete package CRUD from the admin API.

## Endpoints

- `GET /api/v1/admin/packages` (permission: `admin.packages.view`)
- `GET /api/v1/admin/packages/{package}` (permission: `admin.packages.view`)
- `GET /api/v1/admin/packages/permission-options` (permission: `admin.packages.view`)
- `POST /api/v1/admin/packages` (permission: `admin.packages.add`)
- `PATCH /api/v1/admin/packages/{package}` (permission: `admin.packages.edit`)
- `PUT /api/v1/admin/packages/{package}` (permission: `admin.packages.edit`)
- `DELETE /api/v1/admin/packages/{package}` (permission: `admin.packages.delete`, soft delete)

Auth for all endpoints: `Bearer` Sanctum token required.

## Request body

```json
{
    "name": "Gold Plan",
    "code": "gold_plan",
    "description": "Gold package for premium members.",
    "duration_unit": "year",
    "monthly_price": 499,
    "yearly_price": 4999,
    "currency": "INR",
    "is_active": true,
    "is_default_registration": true,
    "is_popular": true,
    "permission_ids": [101, 102, 103],
    "sort_order": 3
}
```

## Validation rules

- `name`: required, string, max 255
- `code`: required, string, max 64, unique in `packages.code`
- `description`: nullable string
- `duration_unit`: required, `month` or `year`
- `monthly_price`: required numeric, minimum 0
- `yearly_price`: required numeric, minimum 0
- `currency`: nullable 3-letter code (stored uppercase)
- `is_active`: nullable boolean
- `is_default_registration`: nullable boolean (only one package should be default)
- `is_popular`: nullable boolean
- `permission_ids`: nullable array of permission ids (candidate permissions only)
- `sort_order`: nullable integer, minimum 0

## Create/Update request body

```json
{
    "name": "Gold Plan",
    "code": "gold_plan",
    "description": "Gold package for premium members.",
    "duration_unit": "year",
    "monthly_price": 499,
    "yearly_price": 4999,
    "currency": "INR",
    "is_active": true,
    "is_default_registration": true,
    "is_popular": true,
    "permission_ids": [101, 102, 103],
    "sort_order": 3
}
```

For update, all fields are optional (`PATCH` semantics).

## Response shape (create/show/update)

```json
{
    "success": true,
    "statusCode": 201,
    "message": "Package created successfully",
    "data": {
        "id": 1,
        "uuid": "....",
        "name": "Gold Plan",
        "code": "GOLD_PLAN",
        "description": "Gold package for premium members.",
        "durationUnit": "year",
        "durationDays": 365,
        "pricePerDay": 9.59,
        "monthlyPrice": 499,
        "yearlyPrice": 4999,
        "price": "4999.00",
        "discountedPrice": null,
        "currency": "INR",
        "isActive": true,
        "isDefaultRegistration": true,
        "isPopular": true,
        "featurePermissions": [
            {
                "id": 101,
                "name": "candidate.browse_profiles.full",
                "title": "Browse profiles (full view)"
            }
        ],
        "sortOrder": 3,
        "createdBy": 10,
        "updatedBy": 10,
        "createdAt": "2026-04-30T06:00:00.000000Z",
        "updatedAt": "2026-04-30T06:00:00.000000Z"
    },
    "error": null,
    "meta": {
        "timestamp": "2026-04-30T06:00:00.000Z",
        "requestId": "req_...",
        "version": "1.0.0"
    }
}
```

## List response

`GET /api/v1/admin/packages?perPage=15` returns the same `data` item shape as above in an array, with pagination in `meta.pagination`.

## Permission options response

`GET /api/v1/admin/packages/permission-options` returns all selectable candidate permissions for package create/update UI checklists.

## Delete response

`DELETE /api/v1/admin/packages/{package}` returns success envelope with message `Package deleted successfully`.

Delete is soft-delete (`deleted_at` is populated); deleted packages are excluded from normal list/show route model binding.

## Event and notification flow

- `PackageObserver` dispatches `PackageCreatedEvent` after insert.
- `PackageCreatedListener` receives the event and notifies all users with role `admin`.
- Notification class: `PackageCreatedNotification` (database channel).

## Test command

```bash
php artisan test tests/Feature/AdminPackageCreateTest.php tests/Feature/AdminPackageCrudTest.php tests/Feature/AuthFlowTest.php
```
