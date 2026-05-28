<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class CandidateEntitlements
{
    public const BROWSE_LIMITED = 'candidate.browse_profiles.limited';

    public const BROWSE_FULL = 'candidate.browse_profiles.full';

    public const VIEW_FULL_PROFILE = 'candidate.view_full_profile_details';

    public const SEND_CONTACT_REQUESTS = 'candidate.send_contact_requests';

    public const MARK_FAVORITE = 'candidate.mark_profiles_favorite';

    public const VIEW_MATCHES = 'candidate.view_my_matches';

    public const VIEW_PARTNER_PREFERENCES = 'candidate.view_partner_preferences_details';

    public const VIEW_LIFESTYLE = 'candidate.view_lifestyle_details';

    public const VIEW_CONTACT_DETAILS = 'candidate.view_contact_details';

    public const VIEW_HIGHLIGHTING = 'candidate.view_profile_highlighting';

    public const VIEW_INSTANT_CONTACT = 'candidate.view_instant_contact_access';

    public const GENERATE_KUNDALI = 'candidate.generate_kundali';

    public const VIEW_KUNDALI = 'candidate.view_kundali';

    public const VIEW_KUNDALI_MATCHING = 'candidate.view_kundali_matching_results';

    public static function canBrowse(?User $user): bool
    {
        return $user !== null && ($user->can(self::BROWSE_LIMITED) || $user->can(self::BROWSE_FULL));
    }

    public static function hasFullBrowse(?User $user): bool
    {
        return $user !== null && $user->can(self::BROWSE_FULL);
    }

    public static function hasLimitedBrowseOnly(?User $user): bool
    {
        return $user !== null && $user->can(self::BROWSE_LIMITED) && !$user->can(self::BROWSE_FULL);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @return array<string, mixed>
     */
    public static function redactPeerProfilePayload(array $payload, User $profile, User $viewer): array
    {
        if ($viewer->id === $profile->id) {
            return $payload;
        }

        if (!$viewer->can(self::VIEW_FULL_PROFILE)) {
            return self::limitedProfilePayload($payload);
        }

        $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

        if (!$viewer->can(self::VIEW_LIFESTYLE)) {
            unset($sections['lifestyle']);
        }

        if (!$viewer->can(self::VIEW_PARTNER_PREFERENCES)) {
            unset($sections['partnerPreferences']);
        }

        if (!$viewer->can(self::VIEW_KUNDALI) && !$viewer->can(self::GENERATE_KUNDALI)) {
            unset($sections['horoscopeDetails']);
        }

        $payload['sections'] = $sections;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @return array<string, mixed>
     */
    private static function limitedProfilePayload(array $payload): array
    {
        $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
        $personal = is_array($sections['personalDetails'] ?? null) ? $sections['personalDetails'] : [];
        $career = is_array($sections['careerEducation'] ?? null) ? $sections['careerEducation'] : [];
        $photos = is_array($sections['photos'] ?? null) ? $sections['photos'] : [];

        return [
            'uuid' => $payload['uuid'] ?? null,
            'firstName' => $payload['firstName'] ?? null,
            'lastName' => $payload['lastName'] ?? null,
            'age' => $personal['age'] ?? null,
            'education' => $payload['education'] ?? null,
            'occupation' => $payload['occupation'] ?? null,
            'phone' => null,
            'profileAccess' => 'limited',
            'sections' => [
                'photos' => array_slice($photos, 0, 1),
                'personalDetails' => [
                    'firstName' => $personal['firstName'] ?? ($payload['firstName'] ?? null),
                    'lastName' => $personal['lastName'] ?? ($payload['lastName'] ?? null),
                    'photoUrl' => $personal['photoUrl'] ?? null,
                    'age' => $personal['age'] ?? null,
                ],
                'careerEducation' => [
                    'occupation' => $career['occupation'] ?? ($payload['occupation'] ?? null),
                    'qualifications' => $career['qualifications'] ?? [],
                ],
            ],
        ];
    }
}
