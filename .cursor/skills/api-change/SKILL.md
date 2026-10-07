---
name: api-change
description: Checklist for Community Connect API route, controller, or service changes. Use when adding or changing endpoints, middleware, resources, or FormRequests.
---

# API change

1. Find the existing route file: `routes/api/v1/auth.php`, `admin.php`, `app.php`, or `public.php`.
2. Keep the controller thin. Put logic in `app/Services`.
3. Return the standard envelope via `ApiResponse` / `ApiResponseBuilder`.
4. Member routes stay behind `auth:sanctum` and `tracked.session`. `me` routes keep `profile.uuid.header`.
5. Guest candidate payloads stay on the public teaser allow-list. See `docs/app_public_cms_api.md`.
6. Add or update a Pest feature test. Run `php artisan test --filter=` for it.
7. If routes or FormRequests changed, run `php artisan postman:generate`.
8. Update the existing doc in `docs/` that already describes that area. Do not add a new markdown file unless the user asked.
9. Run the `php-quality` skill steps before finishing.

Do not treat `app-plan.md` as a spec for unimplemented OTP, Socialite, or Razorpay.
