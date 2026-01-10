# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Created a specialized architecture documentation in `docs/ARCHITECTURE.md`.
- Added `CHANGELOG.md` and `CONTRIBUTING.md` for project repository health.
- Implemented a custom Artisan command `make:api-module` for rapid, standardized feature scaffolding.
- Added a programmatic unit test `tests/Unit/StrictTypesTest.php` to enforce `declare(strict_types=1);` across the codebase.
- Implemented detailed module documentation for the `User` entity in `docs/api-modules/User.md`.
- Established a Git pre-commit hook in `git-hooks/pre-commit` to automate quality checks.

### Changed

- Enforced `declare(strict_types=1);` across all application and test files.
- Refactored `UserController` and `UserService` to follow modern Laravel and PHP 8.2 standards.
- Modernized PHPUnit tests by adopting the `test_` prefix convention for improved discovery and clarity.
- Updated `README.md` with a professional design, production-grade instructions, and a visual architectural overview.
- Optimized the `scripts/setup-project.sh` for a seamless one-command local environment setup.
- Cleaned the documentation and terminal outputs by removing emojis to maintain a professional standard.

### Fixed

- Resolved deprecation warnings in PHPUnit related to attributes and mock configurations.
- Fixed inconsistent return types and missing strict type declarations across the service layer.
- Cleaned up boilerplate code, including the removal of default example tests.

### Removed

- Deleted default `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`.
- Removed deprecated `@test` annotations in favor of standard method naming conventions.
