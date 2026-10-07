# Agent instructions

Read [docs/cursor-knowledge.md](docs/cursor-knowledge.md) before changing this API.

## Quality before you finish PHP work

1. `vendor/bin/pint --dirty` on touched PHP (or the whole dirty set).
2. `vendor/bin/phpcs` on the changed PHP paths.
3. If `app/` changed: `vendor/bin/phpstan analyse` (do not add baseline entries to hide new errors).
4. `php artisan test --filter=` for the feature you touched.

Do not say the task is done if any of those fail. `composer quality` is the full gate; use it when the change is wide. Pre-commit and CI already run the same tools — fix issues here so they do not fail later.

## How to work

- Non-trivial changes: locate routes and services first, then edit. Keep controllers thin.
- Trust routes and Feature tests over `app-plan.md` and phase trackers.
- Do not add OTP, Socialite login, or Razorpay checkout/webhooks unless the user explicitly asks; they are not implemented.
- After route or FormRequest changes, run `php artisan postman:generate`.
- Never commit secrets or `storage/app/firebase/*.json`.

## Subagents

For a large change, explore the route/service map before editing, then implement. Afterward, check the diff against Pint, PHPCS, PHPStan, and the relevant Pest tests. Use a Bugbot-style review only when the user asks.
