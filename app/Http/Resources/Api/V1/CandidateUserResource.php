<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'userType' => 'candidate',
            'roleId' => $user->role_id,
            'role' => data_get($user, 'primaryRole.name'),
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dateOfBirth' => $user->date_of_birth !== null ? (string) $user->date_of_birth : null,
            'maritalStatus' => data_get($user, 'marital_status'),
            'height' => $user->height,
            'currentCity' => $user->current_city,
            'education' => data_get($user, 'highest_education'),
            'occupation' => $user->occupation,
            'annualIncome' => data_get($user, 'annual_income'),
            'status' => $user->status,
        ];
    }
}
