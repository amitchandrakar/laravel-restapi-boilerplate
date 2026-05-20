# MySQL Database Design (Tables + Columns)

## `users`

- `id`
- `uuid`
- `profile_id`
- `first_name`
- `middle_name`
- `last_name`
- `gender`
- `body_type`
- `complexion`
- `height`
- `weight`
- `blood_group`
- `email`
- `phone`
- `password`
- `date_of_birth`
- `time_of_birth`
- `zodiac_sign`
- `place_of_birth_country`
- `place_of_birth_state`
- `place_of_birth_city`
- `current_country`
- `current_state`
- `current_city`
- `current_district`
- `current_village`
- `hometown_country`
- `hometown_state`
- `hometown_city`
- `hometown_district`
- `hometown_village`
- `occupation`
- `employer`
- `income`
- `father_name`
- `father_occupation`
- `father_gotra`
- `father_native_place`
- `mother_name`
- `mother_occupation`
- `mother_gotra`
- `mother_native_place`
- `brothers_count`
- `sisters_count`
- `family_type`
- `family_status`
- `diet`
- `smoking`
- `drinking`
- `preferred_age_range`
- `preferred_height_range`
- `preferred_education`
- `preferred_community`
- `status`
- `created_by`
- `created_at`
- `updated_by`
- `updated_at`
- `deleted_by`
- `deleted_at`

## Core Profile Extension Tables

### `user_images`

- `id`
- `uuid`
- `user_id`
- `image_type` (profile|gallery|verification|other)
- `image_url`
- `thumbnail_url`
- `is_profile_photo`
- `sort_order`
- `is_active`
- `uploaded_by`
- `created_at`
- `updated_at`
- `deleted_at`

### `user_education_details`

- `id`
- `uuid`
- `user_id`
- `degree_id`
- `field_of_study`
- `institution_name`
- `education_type` (school|diploma|graduation|post_graduation|doctorate|other)
- `start_year`
- `end_year`
- `grade_or_percentage`
- `is_highest`
- `notes`
- `created_at`
- `updated_at`
- `deleted_at`

### `user_siblings_details`

- `id`
- `uuid`
- `user_id`
- `name`
- `gender`
- `relation_type` (brother|sister)
- `marital_status`
- `occupation`
- `education`
- `age`
- `is_elder`
- `sort_order`
- `created_at`
- `updated_at`
- `deleted_at`

### `user_partner_preferences`

- `id`
- `uuid`
- `user_id`
- `preferred_gender`
- `preferred_min_age`
- `preferred_max_age`
- `preferred_min_height`
- `preferred_max_height`
- `preferred_marital_status`
- `preferred_diet`
- `preferred_smoking`
- `preferred_drinking`
- `preferred_education`
- `preferred_occupation`
- `preferred_income_min`
- `preferred_community`
- `created_at`
- `updated_at`

## Verification and Compliance

### `user_verification_documents`

- `id`
- `uuid`
- `user_id`
- `document_type` (aadhaar|pan|passport|voter_id|driving_license|other)
- `document_number_masked`
- `document_front_url`
- `document_back_url`
- `selfie_url`
- `verification_status` (pending|approved|rejected|resubmission_required)
- `verified_by`
- `verified_at`
- `rejection_reason`
- `submitted_at`
- `created_at`
- `updated_at`
- `deleted_at`

### `deleted_accounts`

- `id`
- `uuid`
- `user_id`
- `reason`
- `reason_notes`
- `deleted_by_user`
- `deleted_by_admin`
- `deleted_ip`
- `deleted_user_agent`
- `created_at`

### `data_erasure_requests`

- `id`
- `uuid`
- `user_id`
- `request_type` (soft_delete|hard_delete|anonymize)
- `status` (requested|in_review|completed|rejected)
- `requested_at`
- `processed_by`
- `processed_at`
- `rejection_reason`
- `notes`
- `created_at`
- `updated_at`

### `otp_requests`

- `id`
- `uuid`
- `user_id`
- `channel` (sms|email|whatsapp)
- `destination`
- `otp_hash`
- `purpose` (register|login|phone_verify|email_verify|password_reset)
- `attempt_count`
- `max_attempts`
- `status` (sent|verified|expired|failed|blocked)
- `requested_at`
- `expires_at`
- `verified_at`
- `ip_address`
- `user_agent`
- `created_at`
- `updated_at`

## Membership and Payments

### `packages`

- `id`
- `uuid`
- `name`
- `code`
- `description`
- `duration_days`
- `price`
- `discounted_price`
- `currency`
- `is_active`
- `sort_order`
- `created_by`
- `created_at`
- `updated_by`
- `updated_at`
- `deleted_at`

### `subscriptions`

- `id`
- `uuid`
- `user_id`
- `package_id`
- `subscription_status` (active|expired|cancelled|pending)
- `started_at`
- `ends_at`
- `auto_renew`
- `renewal_source` (manual|gateway|admin)
- `last_payment_id`
- `created_at`
- `updated_at`

### `user_membership_history`

- `id`
- `uuid`
- `user_id`
- `package_id`
- `subscription_id`
- `action_type` (started|renewed|upgraded|downgraded|expired|cancelled)
- `amount`
- `currency`
- `action_by`
- `action_source` (user|system|admin)
- `notes`
- `created_at`

### `payments`

- `id`
- `uuid`
- `user_id`
- `subscription_id`
- `package_id`
- `gateway_name`
- `gateway_order_id`
- `gateway_payment_id`
- `gateway_reference_id`
- `amount`
- `currency`
- `payment_status` (pending|success|failed|refunded|cancelled)
- `payment_method` (upi|card|netbanking|wallet|cash|manual)
- `paid_at`
- `failed_reason`
- `raw_response_json`
- `created_at`
- `updated_at`

### `user_payment_history`

- `id`
- `uuid`
- `user_id`
- `payment_id`
- `subscription_id`
- `history_type` (initiated|confirmed|failed|refund_initiated|refunded)
- `amount`
- `currency`
- `remarks`
- `created_at`

## Social/Auth/Admin

### `social_logins`

- `id`
- `uuid`
- `user_id`
- `provider` (google|facebook|apple)
- `provider_user_id`
- `provider_email`
- `access_token_hash`
- `refresh_token_hash`
- `token_expires_at`
- `is_primary`
- `last_login_at`
- `created_at`
- `updated_at`

### `roles`

- `id`
- `uuid`
- `name`
- `code`
- `description`
- `is_active`
- `created_at`
- `updated_at`

### `permissions`

- `id`
- `uuid`
- `name`
- `code`
- `module`
- `description`
- `created_at`
- `updated_at`

### `user_roles`

- `id`
- `uuid`
- `user_id`
- `role_id`
- `assigned_by`
- `assigned_at`
- `is_active`
- `created_at`
- `updated_at`

## Content and App Config

### `settings`

- `id`
- `uuid`
- `group_key`
- `setting_key`
- `setting_value`
- `value_type` (string|number|boolean|json)
- `is_public`
- `is_active`
- `created_at`
- `updated_at`

### `seo_settings`

- `id`
- `uuid`
- `page_key`
- `title`
- `description`
- `keywords`
- `canonical_url`
- `og_title`
- `og_description`
- `og_image_url`
- `is_active`
- `created_at`
- `updated_at`

### `advertisements`

- `id`
- `uuid`
- `title`
- `ad_type` (banner|popup|inline)
- `placement` (home|browse|profile|matches|global)
- `image_url`
- `redirect_url`
- `start_at`
- `end_at`
- `priority`
- `is_active`
- `created_by`
- `created_at`
- `updated_at`

## User Interaction Tables

### `notifications`

- `id`
- `uuid`
- `user_id`
- `type` (match|favorite|contact_request|membership|system)
- `title`
- `message`
- `payload_json`
- `channel` (in_app|push|email|sms)
- `is_read`
- `read_at`
- `sent_at`
- `created_at`

### `favorites`

- `id`
- `uuid`
- `user_id`
- `favorite_user_id`
- `source` (browse|matches|profile)
- `created_at`
- `deleted_at`

### `matches`

- `id`
- `uuid`
- `user_id`
- `matched_user_id`
- `match_score`
- `match_reason_json`
- `match_status` (active|hidden|removed)
- `generated_by` (system|manual)
- `generated_at`
- `created_at`
- `updated_at`

### `contact_requests`

- `id`
- `uuid`
- `from_user_id`
- `to_user_id`
- `request_message`
- `request_status` (pending|accepted|rejected|cancelled)
- `responded_at`
- `response_message`
- `created_at`
- `updated_at`

### `profile_do_not_show`

- `id`
- `uuid`
- `user_id`
- `hidden_user_id`
- `reason`
- `created_at`

### `profile_views`

- `id`
- `uuid`
- `viewer_user_id`
- `viewed_user_id`
- `source` (browse|matches|direct_link|favorites)
- `viewed_at`
- `device_type`
- `created_at`

## Master Data Tables

### `countries`

- `id`
- `name`
- `iso2`
- `iso3`
- `phone_code`
- `is_active`
- `created_at`
- `updated_at`

### `states`

- `id`
- `country_id`
- `name`
- `code`
- `is_active`
- `created_at`
- `updated_at`

### `cities`

- `id`
- `state_id`
- `name`
- `is_active`
- `created_at`
- `updated_at`

### `districts`

- `id`
- `state_id`
- `name`
- `is_active`
- `created_at`
- `updated_at`

### `villages`

- `id`
- `district_id`
- `name`
- `is_active`
- `created_at`
- `updated_at`

### `surnames`

- `id`
- `name`
- `language_id`
- `is_active`
- `created_at`
- `updated_at`

### `languages`

- `id`
- `name`
- `code`
- `is_active`
- `created_at`
- `updated_at`

### `degrees`

- `id`
- `name`
- `degree_type`
- `sort_order`
- `is_active`
- `created_at`
- `updated_at`

### `occupations`

- `id`
- `name`
- `category`
- `sort_order`
- `is_active`
- `created_at`
- `updated_at`

## Logs and Security

### `audit_logs`

- `id`
- `uuid`
- `actor_user_id`
- `entity_type`
- `entity_id`
- `action`
- `old_values_json`
- `new_values_json`
- `ip_address`
- `user_agent`
- `created_at`

### `user_activity_logs`

- `id`
- `uuid`
- `user_id`
- `activity_type`
- `activity_source`
- `metadata_json`
- `ip_address`
- `created_at`

### `user_device_logs`

- `id`
- `uuid`
- `user_id`
- `device_id`
- `device_type`
- `device_name`
- `os_name`
- `os_version`
- `app_version`
- `push_token`
- `last_seen_at`
- `created_at`
- `updated_at`

### `user_sessions`

- `id`
- `uuid`
- `user_id`
- `session_token_hash`
- `refresh_token_hash`
- `login_at`
- `expires_at`
- `logout_at`
- `ip_address`
- `user_agent`
- `device_id`
- `is_active`
- `created_at`
- `updated_at`

## Notes

- Keep one naming pattern: `user_education_details` and `user_siblings_details` (avoid duplicate variants like `user_educational_details` / `siblings_details`).
- Keep preference columns in `users` only if you want denormalized quick filters; otherwise shift to `user_partner_preferences`.
- Keep both `payments` and `user_payment_history` only if event-level payment timeline is needed.
