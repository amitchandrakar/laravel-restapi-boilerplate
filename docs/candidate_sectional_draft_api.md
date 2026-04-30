# Candidate Sectional Draft API

This API supports long candidate profile forms with section-wise save, draft progress tracking, and final publish.

## Modes

- Candidate self-service routes: `/api/v1/auth/candidate/profile/*` (auth candidate)
- Admin-managed routes: `/api/v1/admin/candidates/{user}/*` (admin permissions)

## Sections

- `basics`
- `photos`
- `personal-details`
- `horoscope`
- `location-family-roots`
- `career-education`
- `family-background`
- `lifestyle`
- `partner-preferences`

## Candidate Self Routes

- `PATCH /api/v1/auth/candidate/profile/{section}`
- `GET /api/v1/auth/candidate/profile/progress`
- `POST /api/v1/auth/candidate/profile/publish`

## Admin Routes

- `POST /api/v1/admin/candidates/{user}/sections/{section}`
- `GET /api/v1/admin/candidates/{user}/section-progress`
- `POST /api/v1/admin/candidates/{user}/publish`
- `PUT /api/v1/admin/candidates/{user}/profile` (save all sections in one request)

## Draft/Publish Fields

Saved on `users`:

- `profile_status` (`draft`/`published`)
- `completed_sections_json`
- `published_at`

## Publish Rule

Publish succeeds only when all sections are marked completed.
If not complete, API returns missing section names in validation error details.
