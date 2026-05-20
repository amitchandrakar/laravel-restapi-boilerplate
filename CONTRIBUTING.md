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

We follow **PSR-12** formatting with additional static analysis and documentation rules.

### Quality tools (run before every PR)

| Command | Tool | Purpose |
|---------|------|---------|
| `composer format` | [Pint](https://laravel.com/docs/pint) | Auto-format PHP (`app/`, `tests/`, etc.) |
| `composer lint` | Pint `--test` | CI-safe format check |
| `composer phpcs` | PHPCS + Slevomat | Unused imports/variables, native type hints, doc spacing |
| `composer phpcs:fix` | PHPCBF | Auto-fix a subset of PHPCS issues |
| `composer analyse` | PHPStan 8 + Larastan + strict-rules | Types, dead paths, stricter comparisons |
| `composer test` | Pest | Behaviour |
| `composer quality` | All of the above (except format) | Full local gate |

Configuration lives in:

- [`pint.json`](pint.json) — spacing, imports, blank lines before control flow
- [`phpcs.xml`](phpcs.xml) — Slevomat rules on `app/`
- [`phpstan.neon`](phpstan.neon) — level 8 + [`phpstan-baseline.neon`](phpstan-baseline.neon) for legacy debt
- [`tests/Pest.php`](tests/Pest.php) — Pest + Laravel test case

### Documentation expectations

- **Public methods** on controllers, services, jobs, and other API surface should have a one-line PHPDoc summary.
- Use **native PHP types** on parameters and return types; PHPCS enforces missing hints on non-trivial methods.
- Prefer `expect()` / fluent HTTP assertions in tests; Pest `it()` titles should read as full sentences after Pest prints the leading `it ` (e.g. start with _allows_, _returns_, _rejects_).

### Incremental strictness

- Do **not** add new entries to `phpstan-baseline.neon` without a follow-up issue to fix them.
- **Phase 2** (planned): enable `shipmonk/dead-code-detector` in PHPStan, analyse `tests/` at level 6+, and tighten boolean/loose-comparison strict rules.
- **Phase 3**: require traversable PHPDoc shapes (`array<string, mixed>`) on complex return types.

### Frontend / misc formatting

- `npm run format` — Prettier for JS/JSON/CSS where applicable.

Our **pre-commit hook** runs Prettier, Pint, PHPCS, PHPStan, and tests. Ensure your commit passes locally before pushing.

## Pull Request Process

1.  Create a new feature branch from `main`.
2.  Run `composer quality` (and `composer format` if Pint changed files).
3.  Ensure your code is well-tested (100% feature coverage for new endpoints).
4.  Update the `CHANGELOG.md` under the `[Unreleased]` section.
5.  If adding a new module, provide documentation in `docs/api-modules`.
6.  Submit a descriptive Pull Request for review.
