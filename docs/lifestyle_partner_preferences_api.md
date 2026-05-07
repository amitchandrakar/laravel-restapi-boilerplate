# Lifestyle & Partner Preferences (new fields) — API

This document describes the **recently added** Lifestyle and Partner Preferences fields and how to **save** and **read** them via the API.

The system stores:

- **Lifestyle (self)** on the **`users`** table.
- **Partner Preferences** on **`user_partner_preferences`** as `preferred_*`.

All endpoints use the standard API envelope. See [`API_DOCUMENTATION_INTRO.md`](API_DOCUMENTATION_INTRO.md).

---

## 1) Save Lifestyle (admin-managed section endpoint)

**Method:** `PATCH` (also accepts `PUT`)  
**Endpoint:** `/api/v1/admin/candidates/{user:uuid}/sections/lifestyle`  
**Auth:** `Authorization: Bearer <token>`  
**Middleware:** `auth:sanctum`, `tracked.session`, `permission:admin.candidates.edit`

### Request body (JSON)

All keys are **snake_case**.

#### Single-select strings (nullable)

- `diet`
- `smoking`
- `drinking`
- `sleep_pattern`
- `working_hours`
- `social_personality`
- `dietary_preferences`
- `drinking_habits`
- `smoking_habits`
- `fitness_level`
- `travel_style`
- `communication_style`
- `relationship_with_family`
- `weekend_preference`

#### Multi-select arrays of strings (nullable; stored as JSON arrays)

- `interests`: `string[]`
- `movie_genres`: `string[]`
- `hobbies`: `string[]`
- `likes`: `string[]`
- `dislikes`: `string[]`

### Example request

```json
{
  "sleep_pattern": "Night Owl",
  "working_hours": "Flexible Hours",
  "social_personality": "Ambivert",
  "dietary_preferences": "Vegetarian",
  "drinking_habits": "Never",
  "smoking_habits": "Non-smoker",
  "fitness_level": "Moderately Active",
  "travel_style": "Road Trip Lover",
  "communication_style": "Humorous",
  "relationship_with_family": "Very Close",
  "weekend_preference": "Family Time",
  "interests": ["Photography", "Music"],
  "movie_genres": ["Comedy", "Drama"],
  "hobbies": ["Cycling"],
  "likes": ["Home-cooked meals"],
  "dislikes": ["Negativity"]
}
```

### Success response — **200 OK**

```json
{
  "success": true,
  "statusCode": 200,
  "message": "Candidate section saved successfully",
  "data": {
    "section": "lifestyle",
    "completedSections": ["..."]
  },
  "error": null,
  "meta": { }
}
```

---

## 2) Save Partner Preferences (admin-managed section endpoint)

**Method:** `PATCH` (also accepts `PUT`)  
**Endpoint:** `/api/v1/admin/candidates/{user:uuid}/sections/partner-preferences`  
**Auth:** `Authorization: Bearer <token>`  
**Middleware:** `auth:sanctum`, `tracked.session`, `permission:admin.candidates.edit`

### Request body (JSON)

All keys are **snake_case**.

#### Existing scalar partner preference keys (already supported)

- `preferred_gender`
- `preferred_min_age`
- `preferred_max_age`
- `preferred_min_height`
- `preferred_max_height`
- `preferred_marital_status`
- `preferred_diet`
- `preferred_smoking`
- `preferred_drinking`
- `preferred_occupation`
- `preferred_caste`
- `preferred_income_min`
- `preferred_degree_ids`: `int[]`
- `preferred_location_ids`: `int[]`
- `preferred_community_ids`: `int[]`

#### Newly added scalar `preferred_*` fields (nullable strings)

- `preferred_sleep_pattern`
- `preferred_working_hours`
- `preferred_social_personality`
- `preferred_dietary_preferences`
- `preferred_drinking_habits`
- `preferred_smoking_habits`
- `preferred_fitness_level`
- `preferred_travel_style`
- `preferred_communication_style`
- `preferred_relationship_with_family`
- `preferred_weekend_preference`

#### Newly added preferred multi-select arrays (nullable; stored as JSON arrays)

- `preferred_interests`: `string[]`
- `preferred_movie_genres`: `string[]`
- `preferred_hobbies`: `string[]`
- `preferred_likes`: `string[]`
- `preferred_dislikes`: `string[]`

### Example request

```json
{
  "preferred_sleep_pattern": "Early Bird (Morning Person)",
  "preferred_working_hours": "Standard 9-to-5",
  "preferred_social_personality": "Introvert",
  "preferred_dietary_preferences": "Non-Vegetarian",
  "preferred_drinking_habits": "Occasionally",
  "preferred_smoking_habits": "Non-smoker",
  "preferred_fitness_level": "Fitness Enthusiast",
  "preferred_travel_style": "Budget Traveler",
  "preferred_communication_style": "Soft-spoken",
  "preferred_relationship_with_family": "Close-knit Family",
  "preferred_weekend_preference": "Staying Home",
  "preferred_interests": ["Travel"],
  "preferred_movie_genres": ["Action/Adventure"],
  "preferred_hobbies": ["Trekking/Hiking"],
  "preferred_likes": ["Meaningful Conversations"],
  "preferred_dislikes": ["Rudeness/Lack of Manners"]
}
```

### Success response — **200 OK**

```json
{
  "success": true,
  "statusCode": 200,
  "message": "Candidate section saved successfully",
  "data": {
    "section": "partner_preferences",
    "completedSections": ["..."]
  },
  "error": null,
  "meta": { }
}
```

---

## 3) Read via admin profile-details

**Method:** `GET`  
**Endpoint:** `/api/v1/admin/candidates/{user:uuid}/profile-details`

The payload includes the new values under:

- `data.sections.lifestyle` (camelCase keys, arrays returned as `string[]`)
- `data.sections.partnerPreferences` (camelCase keys, arrays returned as `string[]`)

### Example response excerpt (`data.sections`)

```json
{
  "lifestyle": {
    "diet": "Vegetarian",
    "smoking": "Never",
    "drinking": "Never",
    "sleepPattern": "Night Owl",
    "workingHours": "Flexible Hours",
    "socialPersonality": "Ambivert",
    "dietaryPreferences": "Vegetarian",
    "drinkingHabits": "Never",
    "smokingHabits": "Non-smoker",
    "fitnessLevel": "Moderately Active",
    "travelStyle": "Road Trip Lover",
    "communicationStyle": "Humorous",
    "relationshipWithFamily": "Very Close",
    "weekendPreference": "Family Time",
    "interests": ["Photography", "Music"],
    "movieGenres": ["Comedy", "Drama"],
    "hobbies": ["Cycling"],
    "likes": ["Home-cooked meals"],
    "dislikes": ["Negativity"]
  },
  "partnerPreferences": {
    "preferredMinAge": 22,
    "preferredMaxAge": 28,
    "preferredSleepPattern": "Early Bird (Morning Person)",
    "preferredWorkingHours": "Standard 9-to-5",
    "preferredSocialPersonality": "Introvert",
    "preferredDietaryPreferences": "Non-Vegetarian",
    "preferredDrinkingHabits": "Occasionally",
    "preferredSmokingHabits": "Non-smoker",
    "preferredFitnessLevel": "Fitness Enthusiast",
    "preferredTravelStyle": "Budget Traveler",
    "preferredCommunicationStyle": "Soft-spoken",
    "preferredRelationshipWithFamily": "Close-knit Family",
    "preferredWeekendPreference": "Staying Home",
    "preferredInterests": ["Travel"],
    "preferredMovieGenres": ["Action/Adventure"],
    "preferredHobbies": ["Trekking/Hiking"],
    "preferredLikes": ["Meaningful Conversations"],
    "preferredDislikes": ["Rudeness/Lack of Manners"]
  }
}
```

---

## Implementation references

- Migration: `database/migrations/2026_05_06_120000_add_lifestyle_and_partner_preference_fields.php`\n- Save logic: `app/Services/CandidateProfileSectionService.php` (`saveLifestyleSection`, `savePartnerPreferences`)\n- Validation:\n  - `app/Http/Requests/Api/V1/Candidate/SaveCandidateLifestyleRequest.php`\n  - `app/Http/Requests/Api/V1/Candidate/SaveCandidatePartnerPreferencesRequest.php`\n  - `app/Http/Requests/Api/V1/Candidate/SaveAdminCandidateFullProfileRequest.php`\n- Read payloads:\n  - `app/Services/AdminCandidateProfileDetailsService.php`\n  - `app/Http/Resources/Api/V1/CandidateUserResource.php`\n- Test: `tests/Feature/LifestylePartnerPreferencesFieldsTest.php`

