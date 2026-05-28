# App public CMS API

Unauthenticated read endpoints for the candidate-facing website.

## `GET /api/v1/app/public/site-settings`

Returns branding and contact fields safe for public use (cached ~5 minutes).

## `GET /api/v1/app/public/legal-pages/{slug}`

Returns a **published** legal page (`terms`, `privacy-policy`, `cookie-policy`). Responds **404** when the slug exists but `is_published` is false.
