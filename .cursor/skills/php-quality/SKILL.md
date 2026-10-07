---
name: php-quality
description: Run Pint, PHPCS, PHPStan, and targeted Pest after PHP edits in community-connect-api. Use when changing app, routes, database, or tests PHP.
---

# PHP quality

After editing PHP in this repo, do not finish until the relevant checks pass.

1. `vendor/bin/pint --dirty`
2. `vendor/bin/phpcs` on each changed PHP path (or `composer phpcs` if many files changed)
3. If anything under `app/` changed: `vendor/bin/phpstan analyse`
4. `php artisan test --filter=` for the feature area (discovery, auth, public browse, payments, FCM, and so on)

If a command fails, fix the code and rerun that command. Do not add PHPStan baseline entries to silence new errors. Do not skip tests because the suite is slow; narrow the filter instead.

`composer quality` runs the full set when the change is broad.
