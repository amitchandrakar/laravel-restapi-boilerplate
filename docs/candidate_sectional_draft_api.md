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

### Lifestyle and partner-preferences fields

- **Lifestyle** (`/sections/lifestyle`): now supports additional fields stored on `users`:\n - **Single-select strings**: `sleep_pattern`, `working_hours`, `social_personality`, `dietary_preferences`, `drinking_habits`, `smoking_habits`, `fitness_level`, `travel_style`, `communication_style`, `relationship_with_family`, `weekend_preference` (plus existing `diet`, `smoking`, `drinking`)\n - **Multi-select arrays** (stored as JSON): `interests`, `movie_genres`, `hobbies`, `likes`, `dislikes`\n- **Partner preferences** (`/sections/partner-preferences`): supports the same concept as `preferred_*` columns in `user_partner_preferences`:\n - `preferred_sleep_pattern`, `preferred_working_hours`, `preferred_social_personality`, `preferred_dietary_preferences`, `preferred_drinking_habits`, `preferred_smoking_habits`, `preferred_fitness_level`, `preferred_travel_style`, `preferred_communication_style`, `preferred_relationship_with_family`, `preferred_weekend_preference`\n - JSON arrays: `preferred_interests`, `preferred_movie_genres`, `preferred_hobbies`, `preferred_likes`, `preferred_dislikes`

Full request/response examples: [`lifestyle_partner_preferences_api.md`](lifestyle_partner_preferences_api.md).

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
