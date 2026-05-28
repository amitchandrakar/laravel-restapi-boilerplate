# Admin Storage Settings API

S3 disk configuration in `storage_settings`.

## Endpoints

- `GET /api/v1/admin/settings/storage` (`admin.settings.storage.view`)
- `PUT /api/v1/admin/settings/storage` (`admin.settings.storage.edit`)

`secretAccessKey` is masked on GET. When `isEnabled` is true, values merge into `filesystems.disks.s3`.

## Test

```bash
php artisan test tests/Feature/AdminStorageSettingsTest.php
```
