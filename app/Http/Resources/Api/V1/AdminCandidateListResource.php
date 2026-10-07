<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\AdminCandidateListDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight admin list row — avoids per-candidate detail queries on GET /admin/candidates.
 *
 * @property array{
 *     user: User,
 *     profileImageUrl: string,
 *     profileIconUrl: ?string,
 *     identityVerified?: bool,
 *     profileCompletenessPercent?: int
 * } $resource
 */
class AdminCandidateListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{
         *     user: User,
         *     profileImageUrl: string,
         *     profileIconUrl: ?string,
         *     identityVerified?: bool,
         *     profileCompletenessPercent?: int
         * } $row
         */
        $row = $this->resource;
        $user = $row['user'];
        $photoUrl = $row['profileImageUrl'];

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'profileIconUrl' => $row['profileIconUrl'],
            'userType' => 'candidate',
            'roleId' => $user->role_id,
            'role' => data_get($user, 'primaryRole.name'),
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'currentCity' => $user->current_city,
            'fatherName' => data_get($user, 'father_name'),
            'motherName' => data_get($user, 'mother_name'),
            'currentLocationLine' => AdminCandidateListDataService::currentLocationLine($user),
            'identityVerified' => $row['identityVerified'] ?? false,
            'profileCompletenessPercent' => $row['profileCompletenessPercent'] ?? 0,
            'education' => data_get($user, 'highest_education'),
            'occupation' => $user->occupation,
            'profileStatus' => data_get($user, 'profile_status', 'draft'),
            'status' => $user->status,
            'isFeatured' => $user->is_featured ?? false,
            'sections' => [
                'personalDetails' => [
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'photoUrl' => $photoUrl,
                    'age' => $user->date_of_birth !== null ? $user->date_of_birth->age : null,
                    'subCaste' => data_get($user, 'sub_caste'),
                    'gender' => $user->gender,
                    'maritalStatus' => data_get($user, 'marital_status'),
                ],
                'locationFamilyRoots' => [
                    'current' => [
                        'country' => data_get($user, 'current_country'),
                        'state' => data_get($user, 'current_state'),
                        'city' => data_get($user, 'current_city'),
                        'district' => data_get($user, 'current_district'),
                        'village' => data_get($user, 'current_village'),
                    ],
                    'hometown' => [
                        'country' => data_get($user, 'hometown_country'),
                        'state' => data_get($user, 'hometown_state'),
                        'city' => data_get($user, 'hometown_city'),
                        'district' => data_get($user, 'hometown_district'),
                        'village' => data_get($user, 'hometown_village'),
                    ],
                    'maternal' => [
                        'countryId' => data_get($user, 'maternal_country_id'),
                        'stateId' => data_get($user, 'maternal_state_id'),
                        'cityId' => data_get($user, 'maternal_city_id'),
                        'villageName' => data_get($user, 'maternal_village_name'),
                    ],
                ],
            ],
        ];
    }
}
