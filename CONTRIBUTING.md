# Contributing to Laravel API Boilerplate

Thank you for considering contributing to this project! To maintain a high-quality, production-ready codebase, we adhere to strict architectural and coding standards.

## Development Setup

1.  **Environment**: Ensure you have PHP 8.2+, Composer, and a supported database.
2.  **Initialization**:
    ```bash
    chmod +x scripts/setup-project.sh && ./scripts/setup-project.sh
    ```
    This script installs dependencies and sets up necessary git hooks.

## Architectural Standards

All contributions must follow these core principles:

- **Strict Typing**: Every PHP file must start with `declare(strict_types=1);`.
- **Service Layer**: Business logic must reside in `app/Services`, not in controllers.
- **API Versioning**: New endpoints should be versioned (e.g., `Api/V1`).
- **Standardized Responses**: Use the `ApiResponse` trait for all JSON outputs.
- **Feature Modules**: Use `php artisan make:api-module {Name}` to scaffold new feature sets correctly.

## Coding Standards

We follow the **PSR-12** coding standard.

### Quality Tools

Before submitting a pull request, please run the following checks:

- **Styling**: `./vendor/bin/pint`
- **Formatting**: `npm run format` (Prettier)
- **Static Analysis**: `./vendor/bin/phpstan analyse`
- **Testing**: `php artisan test`

Our **pre-commit hook** will automatically run these checks for you. Ensure your commit passes all local checks before pushing.

## Pull Request Process

1.  Create a new feature branch from `main`.
2.  Ensure your code is well-tested (100% feature coverage for new endpoints).
3.  Update the `CHANGELOG.md` under the `[Unreleased]` section.
4.  If adding a new module, provide documentation in `docs/api-modules`.
5.  Submit a descriptive Pull Request for review.
