# Chhattisgarh Master Data Seeder Notes

This project seeds Chhattisgarh-focused master data using local static files under `database/seeders/data/` for reproducibility.

## Seeders

- `ChhattisgarhMasterGeoSeeder`
    - Ensures `India (IN)` in `countries`
    - Ensures `Chhattisgarh (CG)` in `states`
    - Seeds Chhattisgarh `districts`, `cities`, and `villages`
- `MasterDegreesOccupationsSeeder`
    - Seeds widely used degree and occupation masters with deterministic `sort_order`

## Source Provenance

- Geography lists are based on publicly available administrative references for Chhattisgarh (district and city headquarters) and representative village records.
- Degree and occupation lists are curated to cover broadly used Indian matrimony profile categories.

## Refresh Process

1. Update JSON files in `database/seeders/data/`.
2. Re-run:
    - `php artisan db:seed --class=Database\\Seeders\\ChhattisgarhMasterGeoSeeder`
    - `php artisan db:seed --class=Database\\Seeders\\MasterDegreesOccupationsSeeder`
3. Run validation tests:
    - `php artisan test tests/Feature/ChhattisgarhMasterSeedTest.php`
