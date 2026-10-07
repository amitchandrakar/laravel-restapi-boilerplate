# App public CMS API

Unauthenticated read endpoints for the candidate-facing website.  
Routes live in `routes/api/v1/public.php` (mounted at `/api/v1/app/public`). **No** Bearer token required.

## `GET /api/v1/app/public/site-settings`

Returns branding and contact fields safe for public use (cached ~5 minutes).

## `GET /api/v1/app/public/legal-pages/{slug}`

Returns a **published** legal page (`terms`, `privacy-policy`, `cookie-policy`). Responds **404** when the slug exists but `is_published` is false.

## `GET /api/v1/app/public/candidate-profile-options`

Master lists used by browse filters and registration (surnames, degrees, etc.).

## `GET /api/v1/app/public/featured-candidates`

Paginated featured published candidates (homepage carousel).

## `GET /api/v1/app/public/candidates/search`

Guest browse of **published** candidates. Same filter query params as authenticated discovery:

| Param                     | Notes                                  |
| ------------------------- | -------------------------------------- |
| `page`, `perPage`         | Pagination (`perPage` max 50)          |
| `gender`                  |                                        |
| `min_age`, `max_age`      |                                        |
| `community`               | Surname IDs (comma-separated or array) |
| `city`, `city_id`         |                                        |
| `education`, `occupation` | Degree / occupation IDs                |

**Card fields (allow-list):** `uuid`, `fullName`, `age`, `profileImageUrl`, `educationSummary`, `profileAccess: "public"`.

**Never returned:** `dateOfBirth`, phone, email, city/state, occupation, verification status, `isFavorite`, match percentage, contact details.

No KYC gate, preferred-gender merge, or viewer exclusion lists (guest catalog).

## `GET /api/v1/app/public/candidates/{uuid}`

Guest teaser for a **published** candidate. **404** if missing, not a candidate, or not publicly listable.

**Top-level:** `uuid`, `firstName`, `lastName`, `age`, `phone: null`, `profileAccess: "public"`.

**Sections only:**

- `photos` — first photo only (`url`, `isProfilePhoto`)
- `personalDetails` — `firstName`, `lastName`, `age`, `photoUrl`
- `careerEducation` — `occupation`, `qualifications`

**Omitted:** horoscope, location/family roots, family background, property, lifestyle, partner preferences, email, DOB/TOB, full gallery, contact numbers.

Authenticated members continue to use `/api/v1/app/auth/candidate/search` and `/api/v1/app/auth/candidate/{uuid}/profile-details` for entitlement-aware payloads.
