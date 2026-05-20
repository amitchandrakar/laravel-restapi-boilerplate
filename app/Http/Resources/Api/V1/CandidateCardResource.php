<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{user: User, profileImageUrl: string, educationSummary: string, profileVerificationStatus: string, isFavorite?: bool} $data */
        $data = $this->resource;
        $u = $data['user'];
        $age = $u->date_of_birth !== null ? $u->date_of_birth->age : null;

        $out = [
            'uuid' => $u->uuid,
            'fullName' => trim($u->first_name . ' ' . $u->last_name),
            'firstName' => $u->first_name,
            'lastName' => $u->last_name,
            'age' => $age,
            'currentCity' => $u->current_city,
            'currentState' => $u->current_state,
            'occupation' => $u->occupation,
            'profileImageUrl' => $data['profileImageUrl'],
            'educationSummary' => $data['educationSummary'],
            'profileVerificationStatus' => $data['profileVerificationStatus'],
        ];

        if (array_key_exists('isFavorite', $data)) {
            $out['isFavorite'] = (bool) $data['isFavorite'];
        }

        return $out;
    }
}
