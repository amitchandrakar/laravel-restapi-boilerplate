# Matrimonial Platform — Engineering & Product Guidelines

> This is the single source of truth for the matrimonial platform. It covers the complete product user flow, admin dashboard specification, engineering standards, API design, security, testing, infrastructure, and CI/CD. All engineering, design, and QA work must reference this document.

---

## Table of Contents

**Part A — Product & User Flow**

1. [Onboarding & Authentication Flow](#1-onboarding--authentication-flow)
2. [Verification & Payment Pipeline](#2-verification--payment-pipeline)
3. [Core App Experience & Profile Publishing](#3-core-app-experience--profile-publishing)
4. [Discovery, Matching & Interactivity Features](#4-discovery-matching--interactivity-features)
5. [Account Management](#5-account-management)
6. [Admin Dashboard](#6-admin-dashboard)
7. [Master Data Reference](#7-master-data-reference)

**Part B — Engineering Standards**

8. [Core Engineering Principles](#8-core-engineering-principles)
9. [Authentication & Authorization](#9-authentication--authorization)
10. [Database Standards](#10-database-standards)
11. [API Design & Standard Response Structure](#11-api-design--standard-response-structure)
12. [Service & Domain Layer](#12-service--domain-layer)
13. [Search Architecture — Algolia](#13-search-architecture--algolia)
14. [Queue & Background Jobs](#14-queue--background-jobs)
15. [Caching Strategy](#15-caching-strategy)
16. [File Storage](#16-file-storage)
17. [Notification System](#17-notification-system)
18. [Payment Architecture](#18-payment-architecture)
19. [Security — OWASP Top 10 Compliance & Automated Tests](#19-security--owasp-top-10-compliance--automated-tests)
20. [Testing Standards](#20-testing-standards)
21. [Code Quality & Static Analysis](#21-code-quality--static-analysis)
22. [Logging & Monitoring](#22-logging--monitoring)
23. [Performance & Scalability](#23-performance--scalability)
24. [Infrastructure Architecture](#24-infrastructure-architecture)
25. [CI/CD Pipeline](#25-cicd-pipeline)
26. [Git Standards](#26-git-standards)
27. [Reporting & Analytics](#27-reporting--analytics)
28. [Compliance & Privacy](#28-compliance--privacy)
29. [Disaster Recovery](#29-disaster-recovery)
30. [Dependency Management](#30-dependency-management)
31. [Documentation Standards](#31-documentation-standards)
32. [Pre-Development Checklist](#32-pre-development-checklist)
33. [Technology Stack Reference](#33-technology-stack-reference)

---

# Part A — Product & User Flow

---

## 1. Onboarding & Authentication Flow

### 1.1 Splash Screens

- On first launch, the app displays **two successive splash screens** introducing the brand identity and value proposition.
- After the second splash screen completes, the user is automatically redirected to the **Authentication Gateway**.

### 1.2 Authentication Gateway

The authentication screen is the entry point for both new and returning users. It provides:

- **Traditional Auth:** Sign In, Sign Up, and "Forgot Password" recovery.
- **Passwordless Login:** Sign in using an OTP delivered via SMS.
- **Social Authentication:**
    - LinkedIn Sign-In
    - Facebook Sign-In

### 1.3 Registration

Clicking "Register" or "Sign Up" navigates the user to a dedicated registration form.

**Required Fields:**

| Field                       | Mandatory                       |
| --------------------------- | ------------------------------- |
| First Name                  | Yes                             |
| Last Name                   | Yes                             |
| Phone Number                | Yes — used for OTP verification |
| Password & Confirm Password | Yes                             |
| Email Address               | No                              |

#### 1.3.1 Subscription Plan Selection

- Subscription plan selection is integrated directly into the registration page.
- The user must select a plan before clicking **"Register Now"**.
- Clicking "Register Now" submits the form and initiates the Verification & Payment Pipeline.

---

## 2. Verification & Payment Pipeline

This is a linear, three-step pipeline executed sequentially after registration submission.

### 2.1 Step 1 — OTP Verification

- An automated SMS containing a unique OTP is sent to the user's registered phone number.
- The user must enter the correct OTP to verify their mobile identity.
- Only after successful OTP verification does the user advance to Step 2.

### 2.2 Step 2 — Payment Gateway

- OTP success automatically redirects the user to the Payment Gateway screen.
- **Supported payment methods:** Credit/Debit Cards, UPI, Net Banking, Digital Wallets (via Razorpay).
- **Skip Option:** The user may skip payment and proceed to KYC without completing a transaction.

### 2.3 Step 3 — Identity Verification (KYC)

- Following the payment step (completed or skipped), the user lands on the KYC screen.
- **Submission Requirements:**
    - Front image of Aadhaar Card
    - Back image of Aadhaar Card
    - Live selfie captured via the in-app camera
- **Skip Option:** The user may skip KYC. Skipping routes them directly to the Home Screen in an **unverified / restricted state**. Their profile will not be publicly visible until KYC is completed and admin-approved.

---

## 3. Core App Experience & Profile Publishing

### 3.1 The Home Screen Dashboard

Once onboarding is completed (or skipped), the user arrives at the main application dashboard. The home screen contains:

- **Recently Approved Photos:** A grid of newly approved media from verified platform candidates.
- **Featured Profiles:** A carousel spotlighting premium-tier or highly-rated candidate profiles.
- **View History:** A log showing profiles the user has previously browsed.
- **System & Engagement Stats:** Personalised insights into the user's engagement metrics.

### 3.2 Profile Publishing & Admin Review Workflow

> **Strict Business Rule:** A candidate's profile is completely hidden from all public search feeds, match suggestions, and browse listings until it has been fully verified and manually approved by a system administrator. No profile is ever visible without admin approval.

If a user skipped KYC during onboarding, they must follow this remediation flow to go live:

```
[User Completes All Profile Fields]
        ↓
[Uploads Aadhaar Front + Back + Selfie]
        ↓
[Admin Notified via Email: "A candidate profile is awaiting verification"]
        ↓
[Admin Reviews Documents & Profile Data]
        ↓
[Admin Approves → Profile Status = Published]
        ↓
[Profile is now Visible, Searchable & Interactive]
```

**Remediation Steps:**

1. **Account Details Completion:** The user navigates to their Account Details page and fills all remaining mandatory fields.
2. **Mandatory KYC Upload:** The user uploads Aadhaar (front + back) and a live selfie if previously skipped.
3. **Admin Notification:** On successful submission, the system dispatches an automated email to the Admin panel: _"A candidate profile is waiting for your manual verification."_
4. **Manual Admin Review:** The administrator reviews uploaded documents and profile data for compliance.
5. **Platform Activation:** Once the admin marks the profile as verified, its status changes to **Published** and it immediately becomes visible and searchable to all other candidates.

---

## 4. Discovery, Matching & Interactivity Features

### 4.1 Search & Discovery

- **Smart Partner Matching:** An automated matchmaking engine that parses the user's pre-configured **Partner Preferences** and surfaces ranked profile suggestions.
- **Global Search Directory:** A robust, filterable catalog allowing users to apply custom filters and search across all active platform profiles.
- **Universal Favourites:** Users can tap a star/bookmark icon on any profile card or list view to instantly save it to their personal **Favourites List**.

#### Search Filters Available

| Category     | Filters                                 |
| ------------ | --------------------------------------- |
| Demographics | Age range, Height range, Weight range   |
| Identity     | Religion, Caste, Sub-caste, Gotra       |
| Location     | City, State, Country                    |
| Education    | Degree, Field of Study                  |
| Career       | Occupation, Annual Income range         |
| Lifestyle    | Diet, Smoking, Drinking, Fitness Level  |
| Appearance   | Body Type, Complexion                   |
| Personality  | Social Personality, Communication Style |

### 4.2 Contact Request System

When a user opens another candidate's detailed profile page, they may initiate a direct connection.

**Workflow:**

```
[User taps "Send Contact Request"]
        ↓
[Optional: attaches a personalised introduction message]
        ↓
[Recipient receives a system push notification]
        ↓
[Recipient reads the message and reviews sender's profile]
        ↓
    ┌───────────────────────────────┐
    │  Accept          │   Reject   │
    └───────┬──────────┴──────┬─────┘
            ↓                 ↓
  [Email sent to sender]   [Silent dismiss —
  "Your request was         no notification
   accepted"]               to sender]
```

**Request States:** `Pending` → `Accepted` or `Rejected`

### 4.3 Profile Moderation & Engagement Controls

From any candidate's individual profile view, users have three contextual actions:

| Action                     | Behaviour                                                                                                     |
| -------------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Mark as Favourite**      | Saves profile to the user's curated Favourites list                                                           |
| **Don't See This Profile** | Permanently suppresses this profile from the user's search results, recommendations, and home dashboard feeds |
| **Mark as Spam / Flag**    | Hides the profile locally and sends an alert to the admin dashboard for safety review                         |

---

## 5. Account Management

### 5.1 Account Delete

- A permanent data erasure utility allowing users to deactivate their profile, remove all uploaded media, and purge their personal data from the platform.
- Deletion is processed asynchronously. The user's profile is immediately hidden; data purge completes within 30 days per compliance requirements.

### 5.2 Account Download (Data Portability)

- Users may request a comprehensive export of all their personal data, profile details, uploaded document references, and account activity history.
- Exported as a machine-readable format (JSON or CSV).
- Request is processed asynchronously and the download link is delivered via email within 72 hours.

---

## 6. Admin Dashboard

### 6.1 Dashboard — Overview

The primary admin landing page displays the following metrics and widgets.

**Overview Stats (Summary Cards):**

- Total Users
- Total Candidates
- Total Team Members
- Total Subscriptions
- Total Payments
- Total Referrals

**Charts & Visualisations:**

- Gender Ratio (donut chart)
- Candidates by Location — Top 10 (pie chart)
- Candidates by Age Group (line chart)
- Top Sub-Castes with count (bar chart)

**Revenue Summary:**

- Total Revenue (all-time)
- Month-on-Month Revenue
- Year-on-Year Revenue
- Revenue Breakdown by Subscription Type

**New Registrations:**

- Month-on-Month New Registrations
- Year-on-Year New Registrations

**Active Subscriptions:**

- Month-on-Month Active Subscriptions
- Year-on-Year Active Subscriptions

**Pending KYC:**

- List of all pending KYC applications requiring review

**Recent Payments:**

- List of the most recent payment transactions

**System Health:**

- Database Health status
- Server Health status
- Cache (Redis) Health status
- Search (Algolia) Health status

---

### 6.2 Candidates

#### 6.2.1 Create Candidate

The admin can manually create a candidate profile with the following sections:

**Basic Details**

- First Name, Last Name
- Email, Phone
- Gender, Marital Status
- Height, Weight, Blood Group
- Body Type (Dropdown)
- Complexion (Dropdown)
- About (text area)

**Horoscope Details**

- Manglik Status
- Gotra
- Time of Birth
- Place of Birth (City, State, Country)
- Rashi, Nakshatra

**Education & Occupation Details**

- Multiple Education Entries:
    - Degree, Field of Study, Institute, Year of Passing, Percentage, Is Highest Degree (toggle)
- Occupation (Dropdown)
- Annual Income (Range Dropdown)

**Family Details**

- Father's Name, Father's Occupation (Dropdown), Father's Hometown
- Mother's Name, Mother's Occupation (Dropdown), Mother's Hometown
- Number of Brothers, Number of Sisters
- Sibling Details (multiple): Name, Age, Occupation, Is Elder (toggle)
- Family Type (Dropdown)
- Property Details: Type (House / Plot / Land) + Description

**Lifestyle Details**

- Diet, Smoking, Drinking
- Hobbies (Multi-select Dropdown)
- Social Personality (Dropdown)
- Communication Style (Dropdown)
- Fitness Level (Dropdown)

**Partner Preferences**

- Diet, Smoking, Drinking
- Minimum Monthly Salary
- Location (City, State, Country)
- Age Range (Min – Max)
- Height Range (Min – Max)
- Weight Range (Min – Max)
- Body Type (Dropdown)
- Complexion (Dropdown)
- Social Personality (Dropdown)
- Communication Style (Dropdown)
- Fitness Level (Dropdown)

#### 6.2.2 Candidate Lists

| View                 | Description                                          |
| -------------------- | ---------------------------------------------------- |
| All Candidates       | Complete candidate directory with search and filters |
| Published Profiles   | Profiles that are live and publicly visible          |
| Under Review         | Profiles pending admin KYC approval                  |
| Suspended Profiles   | Profiles suspended by admins                         |
| Featured Profiles    | Profiles currently marked as featured                |
| Deleted Profiles     | Soft-deleted profiles with restore option            |
| Spam Marked Profiles | Profiles flagged by users for spam                   |

---

### 6.3 Team Management

Admin can create and manage team member accounts.

**Create Team Member Fields:**

- First Name, Last Name
- Email, Phone, Password
- Gender
- Profile Photo
- Role (Dropdown): `Admin`, `Reviewer`
- Permissions (Checkbox list — granular permission assignment)
- Location (City, State, Country)
- About

---

### 6.4 Subscription Management

| View                  | Description                                            |
| --------------------- | ------------------------------------------------------ |
| Packages              | All available subscription packages and their features |
| Active Subscriptions  | Currently active user subscriptions                    |
| Expiring Soon         | Subscriptions expiring within the next 7 days          |
| Expired Subscriptions | All expired subscription records                       |
| Subscription History  | Full audit trail of all subscription events            |

---

### 6.5 Payments

Full payment transaction log with filters for date, status, gateway, and user. Supports transaction detail view, refund processing, and invoice download.

---

### 6.6 Settings

All settings are configurable via the admin panel. No environment variable changes are required for operational configuration.

#### General Settings

- Site Name
- Site Logo
- Site Favicon
- Contact Information: Email, Phone, Address

#### Payment Settings — Razorpay

- Status (Enabled / Disabled)
- Environment: `Production` or `Sandbox`
- Live API Key & Live API Secret
- Sandbox API Key & Sandbox API Secret
- Webhook URL: `https://your-domain.com/api/webhook/razorpay`

#### Roles & Permissions

- Manage Roles
- Manage Permissions
- Assign permissions to roles

#### Email Settings — SMTP

- Status (Enabled / Disabled)
- Host, Port, Username, Password, Encryption
- From Address, From Name
- Reply-To Address, Reply-To Name

#### SMS Settings — Twilio

- Status (Enabled / Disabled)
- Account SID, Auth Token
- From Number

#### Push Notification Settings — Firebase Cloud Messaging (FCM)

- Status (Enabled / Disabled)
- Server Key, Client Key, Sender ID

#### Storage Settings — Amazon S3

- Status (Enabled / Disabled)
- Bucket Name, Region
- Access Key, Secret Key
- Endpoint, Base URL

#### Search Settings — Algolia

- Status (Enabled / Disabled)
- Application ID
- Admin API Key
- Search-Only API Key
- Index Name

#### Cache Settings — Redis

- Status (Enabled / Disabled)
- Host, Port, Username, Password, Database

#### Legal Settings

- Terms & Conditions (rich text editor)
- Privacy Policy (rich text editor)
- Cookie Policy (rich text editor)

---

## 7. Master Data Reference

Master data is managed exclusively via database tables and admin-only seeder commands. No public UI is exposed for master data management.

| Table                  | Values                               |
| ---------------------- | ------------------------------------ |
| `sub_castes`           | Community sub-caste list             |
| `gotras`               | Gotra names                          |
| `rashis`               | Zodiac signs (Rashi)                 |
| `nakshatras`           | Birth star names                     |
| `countries`            | Country list                         |
| `states`               | State list (linked to countries)     |
| `cities`               | City list (linked to states)         |
| `villages`             | Village list (linked to cities)      |
| `degrees`              | Academic degree types                |
| `occupations`          | Occupation categories                |
| `income_ranges`        | Salary/income bracket options        |
| `marital_statuses`     | Single, Divorced, Widowed, etc.      |
| `blood_groups`         | A+, A−, B+, B−, O+, O−, AB+, AB−     |
| `weight_ranges`        | Weight bracket options               |
| `body_types`           | Slim, Athletic, Average, Heavy, etc. |
| `complexions`          | Fair, Wheatish, Dark, etc.           |
| `social_personalities` | Introvert, Extrovert, Ambivert, etc. |
| `communication_styles` | Direct, Diplomatic, Expressive, etc. |
| `weekend_preferences`  | Homebody, Outdoor, Social, etc.      |
| `fitness_levels`       | Sedentary, Active, Athletic, etc.    |

---

# Part B — Engineering Standards

---

## 8. Core Engineering Principles

Every decision in the codebase must be guided by these foundational principles. They are the non-negotiable baseline.

### SOLID Principles

| Principle                     | Description                                   | Laravel Example                                          |
| ----------------------------- | --------------------------------------------- | -------------------------------------------------------- |
| **S** — Single Responsibility | A class does one thing only                   | `UserRegistrationService` handles only registration      |
| **O** — Open/Closed           | Open for extension, closed for modification   | Use abstract base classes and interfaces                 |
| **L** — Liskov Substitution   | Subtypes must be substitutable for base types | Implement contracts/interfaces consistently              |
| **I** — Interface Segregation | Many specific interfaces over one general     | `CanSendEmail`, `CanSendSMS` instead of one `Notifiable` |
| **D** — Dependency Inversion  | Depend on abstractions, not concretions       | Inject interfaces via the service container              |

### DRY — Don't Repeat Yourself

- Extract shared logic into service classes, traits, or base classes.
- Use Laravel's Form Requests for shared validation rules.
- Centralise API response formatting using Resource classes.
- Never duplicate query logic — use Eloquent scopes.

### KISS — Keep It Simple, Stupid

- Prefer the simplest solution that works correctly.
- Avoid premature abstractions and over-engineering.
- Do not add layers of indirection without clear justification.

### YAGNI — You Aren't Gonna Need It

- Do not build features not currently required.
- Defer architectural complexity until it is genuinely needed.
- Every abstraction must earn its place.

### Clean Code Standards

- Use meaningful, intention-revealing names for variables, methods, and classes.
- Keep functions short — ideally under 20 lines.
- Keep classes focused — one responsibility per class.
- Avoid magic numbers and strings — use named constants or enums.
- Prefer early returns to reduce nesting.
- Remove dead code; version control preserves history.
- Write self-documenting code; comment the _why_, not the _what_.
- Limit nested conditionals to a maximum of two levels.

### Laravel-Specific Standards

**Thin Controllers** — Controllers should only:

1. Receive the HTTP request
2. Authorise the action (via Policy or Form Request)
3. Call a service method
4. Return a Resource or response

```php
// ✅ Correct
class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, ProfileService $service): ProfileResource
    {
        $profile = $service->update(auth()->user(), $request->validated());
        return new ProfileResource($profile);
    }
}

// ❌ Wrong — business logic in controller
class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->name = $request->name;
        // ... 50 lines of business logic
    }
}
```

**Service Layer** — All business logic lives in service classes.

**Form Requests** — Validation and authorization belong in Form Request classes, never in controllers.

**API Resources** — Use Resource classes for all API responses. Never return raw model instances.

**Policies and Gates** — Use Laravel's authorization features for all access control decisions.

**Events and Listeners** — Decouple side effects from core business logic.

**Queues and Jobs** — Handle all heavy and asynchronous work via queued jobs.

**Observers** — Use for model lifecycle hooks.

**Value Objects and Custom Casts** — Encapsulate domain-specific transformations.

```php
protected $casts = [
    'phone'  => PhoneNumberCast::class,
    'amount' => MoneyCast::class,
];
```

---

## 9. Authentication & Authorization

### Authentication

Use **Laravel Sanctum** for all API token authentication.

```php
// Token issuance on login
$token = $user->createToken('device-name', ['*'], now()->addDays(30))->plainTextToken;
```

**Features to implement:**

- Token-based API authentication
- OTP-based passwordless login (SMS via Twilio)
- Social login: Google, Facebook, Instagram (via Laravel Socialite)
- Device management — list and revoke individual sessions
- Login history with IP address and user agent logging
- Session revocation — single session and all-sessions
- Secure password reset via signed URLs
- Account lockout after 5 consecutive failed login attempts

### Authorization

Use **Spatie Laravel Permission** for Role-Based Access Control (RBAC).

**Roles:** `admin`, `reviewer`, `candidate`

```php
$user->assignRole('reviewer');
$user->can('approve-kyc'); // permission check
```

Use **Policies** for model-level authorization.

```php
class ProfilePolicy
{
    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
```

### Security Hardening

- Rate limit all auth endpoints: login, register, OTP, password reset.
- Use signed URLs for email verification and password reset.
- Hash passwords with `bcrypt` (cost factor ≥ 12).
- Never log or expose raw tokens.
- Enforce CSRF protection on all non-API routes.
- Rotate tokens on privilege escalation.

---

## 10. Database Standards

### Migration Rules

- One migration per change — never edit an existing migration after deployment.
- All migrations must be reversible with a working `down()` method.
- Always define foreign key constraints explicitly.
- Index all columns used in `WHERE`, `ORDER BY`, and `JOIN` clauses.
- Use `unsignedBigInteger` for all foreign key columns.

### Schema Design Principles

- Normalise data — avoid storing redundant information.
- Use UUIDs for all public-facing identifiers (routes, APIs).
- Apply soft deletes (`deleted_at`) to all user-facing models.
- Add audit columns where traceability matters: `created_by`, `updated_by`.
- Use enum types or lookup tables for all status fields.

```php
Schema::create('users', function (Blueprint $table) {
    $table->id(); // internal primary key
    $table->uuid('uuid')->unique(); // public-facing identifier
    $table->string('email')->unique();
    $table->timestamps();
    $table->softDeletes();
});
```

### Query Standards

- Always eager load relationships to prevent N+1 queries — use `with()`, `withCount()`.
- Use cursor pagination for large datasets (search results, feeds).
- Wrap multi-step write operations in database transactions.
- Use Eloquent scopes to encapsulate reusable query logic.
- Route reporting and analytics queries to the read replica, never the primary.

```php
// ✅ Eager loading
$profiles = Profile::with(['user', 'photos', 'preferences'])->cursorPaginate(20);

// ✅ Query scope
public function scopePublished(Builder $query): Builder
{
    return $query->where('status', ProfileStatus::PUBLISHED);
}
```

---

## 11. API Design & Standard Response Structure

### Versioning

All APIs are versioned from day one. Never make breaking changes to a released version.

```
/api/v1/auth/login
/api/v1/profiles
/api/v1/search
```

### Standard Response Structure

Every API response — success or error — must use this exact envelope. Implement it via a shared `ApiResponse` helper class. Never construct raw JSON arrays in controllers.

**Success Response:**

```json
{
    "success": true,
    "statusCode": 200,
    "message": "Profile retrieved successfully.",
    "data": {},
    "error": null,
    "meta": {
        "timestamp": "2026-05-01T11:53:24.415Z",
        "requestId": "req_01KQHP5V4RJ7B34Z0VP6HTA3A6",
        "version": "1.0.0"
    }
}
```

**Error Response:**

```json
{
    "success": false,
    "statusCode": 422,
    "message": "Validation failed.",
    "data": null,
    "error": {
        "code": "VALIDATION_ERROR",
        "fields": {
            "email": ["The email has already been taken."],
            "phone": ["The phone number is invalid."]
        }
    },
    "meta": {
        "timestamp": "2026-05-01T11:53:24.415Z",
        "requestId": "req_01KQHP5V4RJ7B34Z0VP6HTA3A6",
        "version": "1.0.0"
    }
}
```

**Helper usage:**

```php
return ApiResponse::success($data, 'Profile updated successfully.');
return ApiResponse::error('VALIDATION_ERROR', $errors, 422);
```

The `requestId` is a unique identifier attached to every inbound request by middleware, included in structured logs for full request traceability.

### Pagination

Use **cursor pagination** for all user-facing, high-volume endpoints (search, feeds, profiles). Pagination metadata is included inside the `meta` field.

```json
"meta": {
    "timestamp": "...",
    "requestId": "...",
    "version": "1.0.0",
    "pagination": {
        "next_cursor": "eyJpZCI6MTAwfQ",
        "per_page": 20,
        "has_more": true
    }
}
```

### HTTP Status Codes

| Scenario                    | Code |
| --------------------------- | ---- |
| Success with data           | 200  |
| Resource created            | 201  |
| Accepted (async processing) | 202  |
| No content                  | 204  |
| Validation error            | 422  |
| Unauthenticated             | 401  |
| Forbidden                   | 403  |
| Not found                   | 404  |
| Rate limited                | 429  |
| Server error                | 500  |

### Form Requests

Every mutating endpoint must use a dedicated Form Request class.

```php
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('profile'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'religion' => ['required', Rule::in(Religion::values())],
        ];
    }
}
```

---

## 12. Service & Domain Layer

### Service Classes

All business logic belongs in service classes. Services are plain PHP classes resolved via the service container.

```php
final class KycService
{
    public function submit(User $user, array $documents): KycSubmission
    {
        // validate, store documents, notify admin
    }
}
```

### Actions

For single-use, focused operations, use action classes.

```php
final class SendContactRequestAction
{
    public function execute(User $sender, User $receiver, ?string $message): ContactRequest
    {
        // validate, create record, dispatch events
    }
}
```

### Events and Listeners

Decouple side effects from core flows. All listeners that perform I/O must be queued.

```
UserRegistered          → SendWelcomeEmail, CreateProfileShell
KycSubmitted            → NotifyAdminOfPendingKyc
ProfilePublished        → IndexProfileInAlgolia, SendPublishedNotification
ContactRequestAccepted  → SendAcceptanceEmailToSender
PaymentCompleted        → ActivateSubscription, GenerateInvoice
ProfileFlagged          → NotifyAdminOfSpamReport
```

### Observers

```php
class ProfileObserver
{
    public function updated(Profile $profile): void
    {
        if ($profile->wasChanged('status') && $profile->isPublished()) {
            dispatch(new SyncProfileToAlgolia($profile));
        }
    }
}
```

### Strict Types

Every PHP file must begin with:

```php
<?php

declare(strict_types=1);
```

---

## 13. Search Architecture — Algolia

### Why Algolia

Algolia is used for all user-facing profile search and partner matching. Do not use MySQL `LIKE` queries for any user-facing search — they do not scale and do not support relevance ranking.

### Integration

Use **Laravel Scout** with the **Algolia driver**.

```bash
composer require laravel/scout
composer require algolia/algoliasearch-client-php
```

```php
use Laravel\Scout\Searchable;

class Profile extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->display_name,
            'age' => $this->age,
            'religion' => $this->religion,
            'caste' => $this->caste,
            'sub_caste' => $this->sub_caste,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'education' => $this->highest_degree,
            'occupation' => $this->occupation,
            'income_range' => $this->income_range,
            'height' => $this->height_cm,
            'weight' => $this->weight_kg,
            'diet' => $this->diet,
            'smoking' => $this->smoking,
            'drinking' => $this->drinking,
            'body_type' => $this->body_type,
            'complexion' => $this->complexion,
            'is_verified' => $this->isPublished(),
            'tier' => $this->subscription_tier,
            'activity_score' => $this->activity_score,
            'completeness' => $this->profile_completeness_score,
        ];
    }
}
```

### Index Configuration

Configure via the Algolia dashboard or `config/scout.php`:

- **Searchable attributes:** `name`, `city`, `occupation`, `education`
- **Facets (filterable attributes):** `religion`, `caste`, `sub_caste`, `state`, `country`, `diet`, `smoking`, `drinking`, `body_type`, `complexion`, `occupation`, `income_range`, `is_verified`, `tier`
- **Numeric filters:** `age`, `height`, `weight`
- **Custom ranking:** `desc(tier)`, `desc(activity_score)`, `desc(completeness)`
- **Index replicas:** Create replicas for alternate sort orders (newest first, age ascending, etc.)

### Indexing Strategy

- Index profiles asynchronously using queued Scout jobs — never synchronously on the request.
- Trigger re-indexing on: profile update, KYC approval, subscription tier change.
- Remove from index on: profile suspension, deletion, or ban.

```php
$profile->searchable(); // add or update in Algolia
$profile->unsearchable(); // remove from Algolia
```

### Matching Score & Recommendations

Calculate a compatibility score for ranking match suggestions:

- **Preference match %** — how closely the candidate matches the user's partner preferences
- **Activity score** — recently active profiles rank higher
- **Subscription tier weight** — premium profiles are weighted higher
- **Profile completeness** — incomplete profiles rank lower

Recalculate scores on a nightly scheduled job, not on every search request.

### Algolia Health Check

Include Algolia connectivity in the admin's System Health dashboard, verified via a lightweight index ping.

---

## 14. Queue & Background Jobs

### Queue Backend

Use **Redis** as the queue backend in all environments.

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),
```

### Queue Priority Levels

```
critical  → OTPs, payment webhooks
high      → contact/interest notifications, KYC admin alerts
default   → general email, push notifications
low       → Algolia indexing, report generation, analytics
```

### Standard Background Jobs

| Job                              | Queue    | Trigger                    |
| -------------------------------- | -------- | -------------------------- |
| `SendOtpSms`                     | critical | Registration, login        |
| `ProcessPaymentWebhook`          | critical | Razorpay webhook received  |
| `SendContactRequestNotification` | high     | Contact request sent       |
| `NotifyAdminOfPendingKyc`        | high     | KYC submitted              |
| `SendAcceptanceEmail`            | high     | Contact request accepted   |
| `SendEmailNotification`          | default  | General email events       |
| `SendPushNotification`           | default  | Push alerts                |
| `GenerateInvoice`                | default  | Payment success            |
| `ProcessProfilePhoto`            | default  | Photo uploaded             |
| `SyncProfileToAlgolia`           | low      | Profile created/updated    |
| `RecalculateMatchScores`         | low      | Scheduled nightly          |
| `GenerateAnalyticsReport`        | low      | Scheduled daily            |
| `ProcessAccountDeletion`         | low      | Account deletion requested |

### Job Design Rules

- Every job must be idempotent — safe to retry without side effects.
- Set `$tries`, `$timeout`, and `$backoff` on every job class.
- Implement `failed()` to log, alert, or compensate on final failure.

```php
class SyncProfileToAlgolia implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];

    public function failed(Throwable $e): void
    {
        Log::error('Algolia sync failed', [
            'profile_id' => $this->profile->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## 15. Caching Strategy

### Cache Backend

Use **Redis** for all application caching.

### What to Cache

| Data                                    | TTL        | Invalidation Trigger      |
| --------------------------------------- | ---------- | ------------------------- |
| User permissions                        | 10 min     | On role/permission change |
| Master data (religions, castes, cities) | 24 hours   | On seed/update            |
| Featured profiles                       | 5 min      | On feature toggle         |
| Dashboard metrics                       | 15 min     | On schedule               |
| Rate limit counters                     | Per window | Auto-expiry               |
| Search filter options                   | 1 hour     | On master data change     |

### Cache Key Convention

```
{entity}:{id}:{data-type}

Examples:
"user:1234:permissions"
"profile:5678:match-score"
"master:sub-castes"
"dashboard:metrics:overview"
```

### Cache Invalidation

- Invalidate on write using observers or event listeners.
- Use tagged caches for bulk invalidation of related keys.
- Never cache sensitive data: passwords, tokens, KYC document contents.

```php
Cache::tags(['profiles', "user:{$userId}"])->flush();
```

---

## 16. File Storage

### Storage Backend

Use **Amazon S3** for all file storage. Never store files on the local filesystem in production.

### File Categories

| Category                        | Access                     | Processing                             |
| ------------------------------- | -------------------------- | -------------------------------------- |
| Profile photos                  | Public (S3 public bucket)  | Resize, compress, thumbnail generation |
| KYC documents (Aadhaar, selfie) | Private — signed URLs only | Stored as-is for admin review          |
| Invoices / receipts             | Private — signed URLs      | Generated PDF                          |

### Image Processing

- Process all images asynchronously via the `ProcessProfilePhoto` queued job.
- Generate sizes: thumbnail (100×100), medium (400×400), large (800×800).
- Convert to WebP for web delivery.
- Strip EXIF metadata for privacy on all uploads.
- Validate MIME type server-side — never trust the client-provided content type.

### Private File Access

```php
// Signed URL for KYC documents — expires in 15 minutes
$url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
```

---

## 17. Notification System

### Channels

| Channel | Use Case                         | Tool                                               |
| ------- | -------------------------------- | -------------------------------------------------- |
| Email   | Transactional messages, invoices | Laravel Mail + SMTP (configured in Admin Settings) |
| SMS     | OTP delivery, alerts             | Twilio (configured in Admin Settings)              |
| Push    | Mobile real-time alerts          | FCM (configured in Admin Settings)                 |
| In-App  | Notification feed                | Laravel Notifications + Database channel           |

### Trigger Events & Channels

| Event                          | Email | SMS | Push | In-App |
| ------------------------------ | ----- | --- | ---- | ------ |
| Registration OTP               | —     | ✅  | —    | —      |
| KYC Submitted (Admin alert)    | ✅    | —   | —    | —      |
| Profile Published              | ✅    | —   | ✅   | ✅     |
| Contact Request Received       | —     | —   | ✅   | ✅     |
| Contact Request Accepted       | ✅    | —   | ✅   | ✅     |
| Payment Successful             | ✅    | —   | —    | ✅     |
| Subscription Expiring (3 days) | ✅    | —   | ✅   | ✅     |
| Profile Flagged (Admin alert)  | ✅    | —   | —    | —      |

### Notification Design Rules

- Every notification `implements ShouldQueue`.
- Respect user notification preferences stored in `notification_preferences` table.
- Log all sent notifications in `notification_logs` table.
- Never send duplicate notifications — deduplicate before dispatch.
- All notification content must support localisation (i18n).

---

## 18. Payment Architecture

### Gateway

**Razorpay** — UPI, Cards, Net Banking, Wallets.

All gateway credentials and webhook configuration are managed via the Admin Settings panel (Section 6.6).

### Payment Flow

```
[1] User selects subscription package
        ↓
[2] Server creates Order (order_id stored, status = pending)
        ↓
[3] Client receives order_id → Razorpay payment sheet opens
        ↓
[4] User completes payment on gateway
        ↓
[5] Razorpay sends webhook to /api/webhook/razorpay
        ↓
[6] Server verifies webhook HMAC signature
        ↓
[7] Transaction recorded, Subscription activated
        ↓
[8] Invoice generated (queued) → Email sent to user
```

### Database Entities

- `orders` — Payment intent, created before payment begins
- `transactions` — Payment result: success, failure, or refund
- `subscription_histories` — Audit trail of all subscription lifecycle events

### Implementation Rules

- **Always verify webhook signatures** using Razorpay's HMAC before any processing.
- Process webhooks idempotently — use `payment_id` for deduplication.
- Store the raw webhook payload for debugging and audit.
- Never expose Razorpay secret keys in frontend code or version control.
- Handle all scenarios explicitly: success, failure, timeout, and refund.
- All webhook processing dispatches via the `ProcessPaymentWebhook` queued job.

```php
class RazorpayWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $this->verifySignature($request); // throws 403 on invalid signature
        dispatch(new ProcessPaymentWebhook($request->all()));
        return response()->noContent();
    }
}
```

---

## 19. Security — OWASP Top 10 Compliance & Automated Tests

Security is mandatory. Every OWASP Top 10 risk must be addressed in implementation **and** verified by at least one automated test in the Pest test suite.

---

### A01 — Broken Access Control

**Implementation:**

- Apply Policies and Gates to every protected resource.
- Authorise in Form Requests and Policies, never in controllers.
- Verify resource ownership before any read or write operation.
- Deny by default — explicitly grant access.

**Automated Tests:**

```php
it('prevents a user from editing another user\'s profile', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $profile = Profile::factory()->for($owner)->create();

    $this->actingAs($other)
        ->patchJson("/api/v1/profiles/{$profile->uuid}", ['name' => 'Hacked'])
        ->assertForbidden();
});

it('prevents unauthenticated access to protected endpoints', function () {
    $profile = Profile::factory()->create();

    $this->patchJson("/api/v1/profiles/{$profile->uuid}", [])->assertUnauthorized();
});

it('prevents a reviewer from accessing super-admin-only endpoints', function () {
    $reviewer = User::factory()->create()->assignRole('reviewer');

    $this->actingAs($reviewer)->deleteJson('/api/v1/admin/settings')->assertForbidden();
});
```

---

### A02 — Cryptographic Failures

**Implementation:**

- Enforce HTTPS — redirect HTTP to HTTPS at the load balancer.
- Encrypt KYC document paths and sensitive PII fields at rest.
- Hash passwords with `bcrypt` (cost ≥ 12). Never use MD5 or SHA1.
- Use environment variables for all secrets. Never hardcode credentials.

**Automated Tests:**

```php
it('stores passwords as bcrypt hashes and never as plaintext', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    expect($user->password)->not->toBe('secret123')->toStartWith('$2y$');
});

it('does not return the password field in any API response', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/profile/me')->assertJsonMissingPath('data.password');
});

it('generates signed password reset URLs that are rejected after expiry', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $url = URL::temporarySignedRoute('password.reset', now()->subMinute(), ['token' => $token]);

    $this->get($url)->assertForbidden();
});
```

---

### A03 — Injection

**Implementation:**

- Always use Eloquent or parameterised Query Builder. Never interpolate user input into raw SQL.
- Validate and sanitise all inputs via Form Requests before use.

**Automated Tests:**

```php
it('does not execute SQL injection via the search query parameter', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson("/api/v1/search?name=' OR '1'='1");

    $response->assertOk();
    expect($response->json('data'))->toBeArray();
});

it('rejects SQL injection attempts in filter parameters as validation errors', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/v1/search?age_min=' . urlencode('1 OR 1=1; DROP TABLE users;--'))->assertUnprocessable();
});
```

---

### A04 — Insecure Design

**Implementation:**

- Apply rate limiting to all public and authenticated endpoints.
- Threat-model each new feature before building.
- Design for least privilege — grant minimum permissions required.

**Automated Tests:**

```php
it('rate limits the OTP endpoint after 5 consecutive requests', function () {
    $phone = '+919876543210';

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone]);
    }

    $this->postJson('/api/v1/auth/otp/send', ['phone' => $phone])->assertStatus(429);
});

it('rate limits the login endpoint after repeated failures', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
```

---

### A05 — Security Misconfiguration

**Implementation:**

- `APP_DEBUG=false` in production — never expose stack traces.
- Review and restrict CORS to known origins.
- Remove all dev packages from production (`composer install --no-dev`).
- Disable unused routes in production.

**Automated Tests:**

```php
it('does not expose stack traces in production error responses', function () {
    config(['app.debug' => false, 'app.env' => 'production']);

    $response = $this->getJson('/api/v1/nonexistent-endpoint');

    $response->assertNotFound();
    expect($response->json())->not->toHaveKey('exception');
    expect($response->json())->not->toHaveKey('trace');
});

it('rejects CORS preflight requests from unlisted origins', function () {
    $response = $this->withHeaders(['Origin' => 'https://evil.com'])->options('/api/v1/profiles');

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.com');
});
```

---

### A06 — Vulnerable and Outdated Components

**Implementation:**

- Run `composer audit` in the CI pipeline — block deployment on known vulnerabilities.
- Pin all dependencies to specific semver ranges.
- Update dependencies on a monthly schedule.

**Automated Test:**

```php
it('has no known vulnerabilities in Composer dependencies', function () {
    $output = shell_exec('composer audit --format=json 2>&1');
    $report = json_decode($output, true);

    expect($report['advisories'] ?? [])->toBeEmpty(
        'Known security advisories found. Run `composer audit` for details.'
    );
});
```

---

### A07 — Identification and Authentication Failures

**Implementation:**

- Enforce minimum password complexity (8+ characters, mixed case, number, symbol).
- Lock account after 5 failed login attempts.
- 2FA mandatory for admin accounts; optional for users.
- Expire tokens after 30 days of inactivity.
- Log all authentication events.

**Automated Tests:**

```php
it('locks an account after 5 failed login attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertStatus(423); // Locked
});

it('rejects expired authentication tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['*'], now()->subDay())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/profile/me')->assertUnauthorized();
});

it('rejects weak passwords during registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'password' => '123456',
        'password_confirmation' => '123456',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
```

---

### A08 — Software and Data Integrity Failures

**Implementation:**

- Verify all Razorpay webhook signatures via HMAC before processing.
- Use signed URLs for all private file access and sensitive action links.
- Validate all incoming data against expected types and ranges.

**Automated Tests:**

```php
it('rejects Razorpay webhooks with an invalid HMAC signature', function () {
    $this->postJson(
        '/api/webhook/razorpay',
        ['event' => 'payment.captured'],
        [
            'X-Razorpay-Signature' => 'invalid-signature',
        ]
    )->assertForbidden();
});

it('rejects Razorpay webhooks missing the signature header entirely', function () {
    $this->postJson('/api/webhook/razorpay', ['event' => 'payment.captured'])->assertForbidden();
});

it('rejects expired signed URLs for KYC document access', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('kyc.document', now()->subMinutes(20), ['user' => $user->uuid]);

    $this->actingAs($user)->get($url)->assertForbidden();
});
```

---

### A09 — Security Logging and Monitoring Failures

**Implementation:**

- Log all security events: login, logout, failed auth, permission denied, account lockout.
- Centralise all logs — never store only on the application server.
- Alert on anomalous patterns via Sentry.

**Automated Tests:**

```php
it('logs a failed login attempt with ip address and user agent', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('login_attempts', [
        'user_id' => $user->id,
        'success' => false,
    ]);
});

it('logs a successful login event', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertDatabaseHas('login_attempts', [
        'user_id' => $user->id,
        'success' => true,
    ]);
});
```

---

### A10 — Server-Side Request Forgery (SSRF)

**Implementation:**

- Validate and whitelist external URLs before making any server-side HTTP requests.
- Block requests to `localhost`, `127.0.0.1`, `169.254.x.x`, and all RFC-1918 private IP ranges.
- Do not expose internal service addresses via the API.

**Automated Tests:**

```php
it('rejects photo URLs pointing to localhost', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/profile/photo-url', [
            'url' => 'http://localhost/internal-service',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url']);
});

it('rejects photo URLs pointing to internal RFC-1918 IP ranges', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/profile/photo-url', [
            'url' => 'http://192.168.1.1/secret',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url']);
});
```

---

## 20. Testing Standards

### Testing Pyramid

```
         [ Performance Tests — k6 ]
           [ Security Tests — OWASP ]
              [ Integration Tests ]
           [ Feature / HTTP Tests ]
         [ Unit Tests ] ← majority
```

### Test Types

**Unit Tests** — Service classes, action classes, value objects, domain logic.

```php
it('calculates a compatibility score within valid bounds', function () {
    $score = app(MatchScoreCalculator::class)->calculate($profile, $preferences);
    expect($score)->toBeBetween(0, 100);
});
```

**Feature Tests** — Full HTTP request/response cycle.

```php
it('authenticated user can send a contact request with a message', function () {
    $sender = User::factory()->published()->create();
    $receiver = User::factory()->published()->create();

    $response = $this->actingAs($sender)->postJson("/api/v1/profiles/{$receiver->uuid}/contact-request", [
        'message' => 'Hello, I would like to connect.',
    ]);

    $response->assertCreated()->assertJsonPath('success', true);

    $this->assertDatabaseHas('contact_requests', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'status' => 'pending',
    ]);
});
```

**Integration Tests** — Payment webhooks, Algolia sync, queue processing.

**Performance Tests** — Use **k6** for load and stress testing on search and profile listing endpoints.

**Security Tests** — One automated test per OWASP Top 10 category (see Section 19).

### Testing Rules

- Every bug fix must include a regression test.
- Every new feature must ship with test coverage.
- Critical flows must have 100% feature test coverage: registration, OTP, KYC, payment, contact request, admin approval.
- Use **Pest** as the testing framework.
- Use factories for all test data — never use production data in tests.
- Mock all external services (Algolia, Razorpay, Twilio, FCM) in unit and feature tests.
- Use `RefreshDatabase` trait for test isolation.

---

## 21. Code Quality & Static Analysis

### Static Analysis

Use **Larastan** (PHPStan for Laravel) at level 8 or higher.

```bash
./vendor/bin/phpstan analyse --level=8
```

### Code Formatting

Use **Laravel Pint** enforced in CI. PRs with unformatted code are blocked from merging.

```bash
./vendor/bin/pint
./vendor/bin/pint --test   # CI check — exits non-zero if formatting is needed
```

### Complexity Rules

- Maximum cyclomatic complexity per method: **10**
- Maximum method length: **20 lines**
- Maximum class length: **200 lines**

### PHP Standards

- `declare(strict_types=1)` on every file.
- PSR-12 coding standards.
- Fully typed: properties, return types, parameter types throughout.
- Use PHP 8.3+ features: enums, readonly properties, named arguments, match expressions.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Kyc\Services;

final class KycService
{
    public function __construct(private readonly KycRepository $repository, private readonly StorageService $storage) {}

    public function submit(User $user, KycDocuments $documents): KycSubmission
    {
        // ...
    }
}
```

---

## 22. Logging & Monitoring

### Logging Standards

- Use **structured JSON logs** — every entry is machine-parseable.
- Attach a unique `request_id` to every inbound request via middleware. Include it in all log entries.
- Always include relevant context: `user_id`, `action`, `duration_ms`, `status`.
- Never log sensitive data: passwords, tokens, Aadhaar numbers, card numbers, KYC document contents.

```php
Log::info('Contact request sent', [
    'request_id' => request()->id(),
    'sender_id' => $sender->id,
    'receiver_id' => $receiver->id,
]);
```

### Log Levels

| Level       | When to Use                                 |
| ----------- | ------------------------------------------- |
| `emergency` | System is unusable                          |
| `critical`  | Payment failure, data loss risk             |
| `error`     | Runtime exceptions, unhandled errors        |
| `warning`   | Unexpected state, deprecated usage          |
| `info`      | Normal significant events                   |
| `debug`     | Detailed debugging (disabled in production) |

### Monitoring Stack

| Tool                            | Purpose                                 |
| ------------------------------- | --------------------------------------- |
| **Sentry**                      | Exception tracking and error alerting   |
| **Prometheus**                  | Metrics collection                      |
| **Grafana**                     | Dashboards for metric visualisation     |
| **Centralised Log Aggregation** | Loki, CloudWatch Logs, or Elasticsearch |

### Key Metrics to Monitor

- HTTP 5xx error rate
- API response time (p50, p95, p99)
- Queue depth and job failure rate
- Algolia sync job failure rate
- Database query time
- Cache hit/miss ratio
- Payment success/failure rate

### Alerting Thresholds

- 5xx error rate > 1% → Sentry alert
- Queue depth > 500 jobs → Pager alert
- Payment webhook failure → Immediate Sentry alert
- Failed login spike > 50/min → Security alert
- Database connection pool exhaustion → Critical alert

---

## 23. Performance & Scalability

### Database Performance

- Route all read-heavy queries, reports, and analytics to the **read replica**.
- Add composite indexes for multi-column query patterns.
- Run `EXPLAIN` on all slow queries identified in the query log.
- Cache expensive aggregations in Redis.

### Application Performance

- Profile with Laravel Telescope in development.
- Use `select()` to fetch only required columns.
- Compress API responses with gzip at the Nginx level.
- Use HTTP caching headers (`ETag`, `Cache-Control`) for stable endpoints.

### Horizontal Scaling

- The application must be stateless — no local session or file storage.
- Use Redis for sessions, cache, and queues.
- Store all files on S3.
- Use environment variables for all configuration.
- Load balance across multiple PHP-FPM instances.

### Rate Limiting

Apply rate limits per endpoint category.

```php
// Auth endpoints — strict
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/auth/login');
    Route::post('/auth/otp/send');
});

// Authenticated general endpoints
Route::middleware(['throttle:60,1'])->group(function () {
    // standard API routes
});
```

---

## 24. Infrastructure Architecture

```
                    ┌──────────────────────┐
         Users ──→  │    Load Balancer      │
                    └──────────┬───────────┘
                               │
              ┌────────────────┴───────────────┐
              │                                │
    ┌─────────▼──────────┐    ┌────────────────▼────────┐
    │   App Server #1     │    │     App Server #2        │
    │  (PHP-FPM + Nginx)  │    │   (PHP-FPM + Nginx)      │
    └─────────┬──────────┘    └────────────────┬────────┘
              └───────────────┬────────────────┘
                              │
        ┌─────────────────────┼────────────────────┐
        │                     │                    │
┌───────▼────────┐  ┌─────────▼────────┐  ┌───────▼──────┐
│ Redis Cluster  │  │  MySQL Primary   │  │   Algolia    │
│ (Cache+Queue)  │  │ + Read Replica   │  │ (Search SaaS)│
└────────────────┘  └──────────────────┘  └──────────────┘
        │
┌───────▼────────┐
│   Amazon S3    │
│  (File Store)  │
└────────────────┘
```

**Application Layer:** Multiple PHP-FPM servers behind a load balancer, containerised with Docker.

**Database Layer:** MySQL primary for all writes; read replica(s) for reads, reports, and analytics.

**Cache + Queue Layer:** Dedicated Redis cluster. Cache and queue run on separate Redis logical databases.

**Search Layer:** Algolia cloud-hosted — no search infrastructure to manage.

**Queue Workers:** Dedicated queue worker processes managed by Supervisor, separate from web servers, scaled independently per queue.

---

## 25. CI/CD Pipeline

### Overview

The pipeline runs on **GitHub Actions** with every push and pull request. Deployment to production only occurs when all quality gates pass and the target branch is `main`.

### Full Pipeline Definition

```yaml
name: CI/CD Pipeline

on:
    push:
        branches: [main, develop]
    pull_request:
        branches: [main, develop]

jobs:
    # ─────────────────────────────────────────────────────────
    # STAGE 1: Quality Gates — runs on all branches / all PRs
    # ─────────────────────────────────────────────────────────
    quality:
        name: Quality Gates
        runs-on: ubuntu-latest
        steps:
            - name: Checkout code
              uses: actions/checkout@v4

            - name: Setup PHP 8.3
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.3'
                  extensions: mbstring, pdo, redis

            - name: Install Composer dependencies
              run: composer install --prefer-dist --no-progress

            - name: Check code formatting (Laravel Pint)
              run: ./vendor/bin/pint --test

            - name: Run static analysis (Larastan Level 8)
              run: ./vendor/bin/phpstan analyse --level=8 --no-progress

            - name: Run Composer security audit
              run: composer audit

            - name: Copy environment file
              run: cp .env.testing .env

            - name: Generate application key
              run: php artisan key:generate

            - name: Run full test suite with coverage (Pest)
              run: ./vendor/bin/pest --coverage --min=80 --parallel

    # ─────────────────────────────────────────────────────────
    # STAGE 2: Build — only on main, after quality passes
    # ─────────────────────────────────────────────────────────
    build:
        name: Build Docker Image
        needs: quality
        runs-on: ubuntu-latest
        if: github.ref == 'refs/heads/main'
        steps:
            - name: Checkout code
              uses: actions/checkout@v4

            - name: Build Docker image
              run: docker build -t matrimonial-app:${{ github.sha }} .

            - name: Push image to container registry
              run: docker push registry.example.com/matrimonial-app:${{ github.sha }}

    # ─────────────────────────────────────────────────────────
    # STAGE 3: Deploy — only on main, after build passes
    # ─────────────────────────────────────────────────────────
    deploy:
        name: Deploy to Production
        needs: build
        runs-on: ubuntu-latest
        if: github.ref == 'refs/heads/main'
        steps:
            - name: '[1/10] Notify admin — deployment starting'
              run: |
                  curl -s -X POST "${{ secrets.ADMIN_EMAIL_WEBHOOK }}" \
                    -d "subject=Deployment Starting&body=Deployment of commit ${{ github.sha }} has begun. The server will enter maintenance mode shortly."

            - name: '[2/10] Enable maintenance mode — server goes down'
              run: |
                  ssh deploy@production \
                    "php artisan down --secret=${{ secrets.MAINTENANCE_SECRET }} --render=maintenance"

            - name: '[3/10] Pull latest Docker image on the production server'
              run: |
                  ssh deploy@production \
                    "docker pull registry.example.com/matrimonial-app:${{ github.sha }}"

            - name: '[4/10] Run database migrations'
              run: |
                  ssh deploy@production \
                    "docker run --rm --env-file /etc/app/.env \
                     registry.example.com/matrimonial-app:${{ github.sha }} \
                     php artisan migrate --force"

            - name: '[5/10] Swap to new application container'
              run: |
                  ssh deploy@production \
                    "docker-compose up -d --no-deps --force-recreate app"

            - name: '[6/10] Warm all application caches'
              run: |
                  ssh deploy@production "php artisan config:cache"
                  ssh deploy@production "php artisan route:cache"
                  ssh deploy@production "php artisan view:cache"
                  ssh deploy@production "php artisan event:cache"

            - name: '[7/10] Gracefully restart all queue workers'
              run: |
                  ssh deploy@production "php artisan queue:restart"

            - name: '[8/10] Disable maintenance mode — server comes back up'
              run: |
                  ssh deploy@production "php artisan up"

            - name: '[9/10] Run smoke tests against production'
              run: |
                  # Health endpoint
                  curl --fail https://api.yourdomain.com/api/v1/health || exit 1
                  # Auth endpoint reachable (422 = validation, not 500)
                  STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
                    -X POST https://api.yourdomain.com/api/v1/auth/login \
                    -H "Content-Type: application/json" -d '{}')
                  [ "$STATUS" = "422" ] || exit 1
                  # Algolia health endpoint
                  curl --fail https://api.yourdomain.com/api/v1/health/search || exit 1
                  # Redis health endpoint
                  curl --fail https://api.yourdomain.com/api/v1/health/cache || exit 1

            - name: '[10/10] Notify admin — deployment successful'
              run: |
                  curl -s -X POST "${{ secrets.ADMIN_EMAIL_WEBHOOK }}" \
                    -d "subject=✅ Deployment Successful&body=Deployment of commit ${{ github.sha }} completed at $(date -u). All smoke tests passed. The platform is fully operational."

            # ── Automatic rollback on any step failure ────────────
            - name: 'ROLLBACK — Restore previous stable container'
              if: failure()
              run: |
                  ssh deploy@production \
                    "docker-compose up -d --no-deps --force-recreate app"
                  ssh deploy@production "php artisan up"

            - name: 'ROLLBACK — Notify admin of deployment failure'
              if: failure()
              run: |
                  curl -s -X POST "${{ secrets.ADMIN_EMAIL_WEBHOOK }}" \
                    -d "subject=⚠️ Deployment FAILED — Rollback Executed&body=Deployment of commit ${{ github.sha }} failed. The server has been automatically rolled back to the previous stable version. Immediate investigation is required."
```

### Quality Gates — PR Merge Blockers

A pull request cannot be merged if any of the following fail:

- Any Pest test fails
- Larastan static analysis violations exist
- Laravel Pint formatting check fails
- Code coverage drops below 80% on critical modules
- `composer audit` reports known security vulnerabilities

---

## 26. Git Standards

### Branch Strategy

```
main        ← production (protected, requires PR + 1 reviewer minimum)
develop     ← integration branch (all features merge here first)
feature/*   ← new features (branched from develop)
hotfix/*    ← emergency production fixes (branched from main)
release/*   ← release preparation and staging validation
```

### Commit Message Format (Conventional Commits)

```
feat: add contact request message preview on notification screen
fix: resolve duplicate OTP dispatch on rapid registration taps
refactor: extract payment webhook signature verification into service
test: add OWASP A03 injection tests for the search endpoint
docs: update API response envelope documentation
chore: upgrade Razorpay SDK to v2.9.0
```

### Pull Request Rules

- Minimum one code review from a team member before merge.
- All CI checks must pass before merge is allowed.
- PRs must include test coverage for all new functionality.
- Squash commits before merging to keep history linear and clean.
- Link every PR to its corresponding issue or task.

---

## 27. Reporting & Analytics

### Metrics to Track

- Daily/Monthly Active Users (DAU/MAU)
- New registrations (daily, MoM, YoY)
- Profile completion rate
- KYC submission-to-approval rate and average turnaround time
- Paid subscription conversions
- Revenue by package, period, and payment method
- Search queries per day, top filters used
- Contact request send/accept/reject rates
- Subscription renewal and churn rates

### Implementation

- The admin dashboard (Section 6.1) is the primary operational metrics view.
- Heavy aggregation queries run against the read replica, never the primary.
- Store aggregated metric snapshots in an `analytics_snapshots` table to avoid expensive real-time aggregation on every page load.
- Generate scheduled summary reports (daily, weekly, monthly) via queued jobs, delivered to admin via email.

---

## 28. Compliance & Privacy

### Data Protection (DPDP / GDPR Alignment)

- **Consent management** — Collect and store explicit consent for data processing at registration.
- **Data export** — Allow users to download all personal data (Section 5.2). Delivered within 72 hours.
- **Account deletion** — Full data erasure within 30 days of request (Section 5.1).
- **Audit trails** — Immutable logs of all data access and modifications stored in `audit_logs`.
- **Document retention** — KYC documents retained for 5 years per regulatory requirements; purged automatically thereafter.
- **Data minimisation** — Collect only what is strictly necessary for the service.
- **PII encryption** — Encrypt sensitive fields at rest: Aadhaar number references, raw document paths.

### User Rights

| Right                | Mechanism                               | SLA       |
| -------------------- | --------------------------------------- | --------- |
| Right to Access      | Account download (Section 5.2)          | 72 hours  |
| Right to Erasure     | Account deletion workflow (Section 5.1) | 30 days   |
| Right to Correction  | Profile edit on all fields              | Immediate |
| Right to Portability | JSON/CSV export                         | 72 hours  |

---

## 29. Disaster Recovery

### Backup Strategy

- Automated daily MySQL backups with point-in-time recovery enabled.
- Backups stored in a separate AWS region from the primary.
- Backup restoration tested quarterly.
- S3 cross-region replication enabled for all media and KYC documents.

### Recovery Targets

| Metric                         | Target    |
| ------------------------------ | --------- |
| Recovery Time Objective (RTO)  | < 4 hours |
| Recovery Point Objective (RPO) | < 1 hour  |

### Recovery Drills

- Full recovery drill conducted every quarter.
- Step-by-step recovery runbook documented in `docs/runbooks/disaster-recovery.md`.
- Recovery responsibilities assigned per team role.
- Database failover to read replica tested as part of quarterly drills.

---

## 30. Dependency Management

### Rules

- Only add packages from reputable, actively maintained sources.
- Pin all dependencies to specific semver ranges (`"^2.1.0"`).
- Remove unused packages — they increase the attack surface.
- Run `composer audit` in CI to detect known vulnerabilities.
- Review changelogs before upgrading minor or major versions.
- Dev dependencies (`--dev`) must never ship to production.

### Update Schedule

| Type             | Action                                       |
| ---------------- | -------------------------------------------- |
| Security patches | Apply within 48 hours of disclosure          |
| Minor updates    | Monthly review and update cycle              |
| Major versions   | Planned upgrade with full regression testing |

---

## 31. Documentation Standards

### What to Document

| Document             | Owner         | Location                          |
| -------------------- | ------------- | --------------------------------- |
| API Reference        | Backend team  | Postman collection / OpenAPI spec |
| Database Schema      | Backend team  | `docs/schema.md` or dbdocs.io     |
| Module Documentation | Module owner  | `docs/modules/{module}.md`        |
| ADRs                 | Team          | `docs/adr/`                       |
| Runbooks             | DevOps        | `docs/runbooks/`                  |
| Onboarding Guide     | Lead Engineer | `docs/onboarding.md`              |
| This Document        | Lead Engineer | `docs/guidelines.md`              |

### API Documentation

- Document every endpoint: method, URL, path parameters, query parameters, request body, all response shapes.
- Include example requests and responses.
- Treat documentation as production code — it must be updated in the same PR as the feature.
- Use OpenAPI/Swagger for auto-generated interactive API docs.

### Architecture Decision Records (ADRs)

Record significant technical decisions in short ADR documents.

```markdown
# ADR-001: Use Algolia for Profile Search

## Status: Accepted

## Context

MySQL LIKE queries cannot support multi-attribute filtering, relevance
ranking, and partial-match search at platform scale.

## Decision

Use Algolia via Laravel Scout for all user-facing profile search and
partner matching. Algolia manages infrastructure; we manage index config.

## Consequences

- Scalable, fast, relevance-ranked search out of the box
- No search infrastructure to provision or maintain
  − Monthly cost scales with record count and operations
  − Core search feature depends on a third-party SaaS
```

---

## 32. Pre-Development Checklist

Before writing the first line of application code, confirm all of the following.

### Architecture

- [ ] Domain boundaries are defined and documented
- [ ] Database schema is designed and peer-reviewed
- [ ] API contracts are documented (OpenAPI spec or Postman collection)
- [ ] Algolia index schema, facets, and custom ranking are planned
- [ ] Payment workflow is documented end-to-end including webhook handling
- [ ] KYC workflow and admin review flow is documented

### Engineering

- [ ] Queue jobs are identified per domain with assigned priority levels
- [ ] Cache strategy is documented per data type with TTLs
- [ ] OWASP Top 10 is addressed in the implementation plan
- [ ] Rate limiting strategy is defined per endpoint category
- [ ] Standard API response envelope is implemented as a shared helper
- [ ] `declare(strict_types=1)` enforced via Pint/PHPStan config

### Operations

- [ ] Sentry configured for error tracking in all environments
- [ ] Prometheus + Grafana configured for infrastructure metrics
- [ ] Centralised log aggregation configured
- [ ] CI/CD pipeline set up with all quality gates active
- [ ] Backup and recovery strategy documented and tested
- [ ] Environments provisioned: local, staging, production

### Team

- [ ] This document shared with and acknowledged by all team members
- [ ] Git branching and PR process agreed upon
- [ ] Testing requirements communicated
- [ ] Onboarding documentation written

---

## 33. Technology Stack Reference

| Layer                   | Technology                          | Purpose                              |
| ----------------------- | ----------------------------------- | ------------------------------------ |
| **Language**            | PHP 8.3+                            | Strict types required throughout     |
| **Framework**           | Laravel (latest stable)             | Application framework                |
| **Database**            | MySQL 8.0+                          | Primary write + read replica         |
| **Cache / Queue**       | Redis                               | Dedicated per use case               |
| **Search**              | Algolia                             | Profile search and matching          |
| **Object Storage**      | Amazon S3                           | Profile photos, KYC docs, invoices   |
| **Auth**                | Laravel Sanctum                     | API token authentication             |
| **RBAC**                | Spatie Laravel Permission           | Role and permission management       |
| **Social Auth**         | Laravel Socialite                   | LinkedIn, Facebook login             |
| **Search Integration**  | Laravel Scout + Algolia driver      | ORM-level search indexing            |
| **Payments**            | Razorpay                            | UPI, Cards, Net Banking, Wallets     |
| **SMS**                 | Twilio                              | OTP and alert delivery               |
| **Push Notifications**  | Firebase Cloud Messaging            | Android and iOS push                 |
| **Email**               | SMTP (configured in Admin Settings) | Transactional email                  |
| **Error Tracking**      | Sentry                              | All environments                     |
| **Metrics**             | Prometheus + Grafana                | Infrastructure and app metrics       |
| **Web Server**          | Nginx + PHP-FPM                     | Application serving                  |
| **Containerisation**    | Docker                              | All environments                     |
| **CI/CD**               | GitHub Actions                      | Pipeline automation                  |
| **Testing**             | Pest                                | Unit, Feature, Integration, Security |
| **Static Analysis**     | Larastan (PHPStan Level 8+)         | Type and logic correctness           |
| **Code Formatting**     | Laravel Pint                        | Enforced in CI                       |
| **Performance Testing** | k6                                  | Load and stress testing              |

---

_This document is the single source of truth for the platform. It must be updated when product requirements change, architectural decisions are revised, or engineering standards evolve. All team members — engineering, QA, and design — are expected to read, understand, and follow this document._

php artisan scout:sync-index-settings
php artisan candidates:sync-algolia
php artisan queue:work redis --queue=critical,high,default,low
