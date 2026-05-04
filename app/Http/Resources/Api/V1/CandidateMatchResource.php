<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'matchUuid' => $this->resource['matchUuid'],
            'matchPercentage' => $this->resource['matchPercentage'],
            'hasPremiumSubscription' => $this->resource['hasPremiumSubscription'],
            'isVerified' => $this->resource['isVerified'],
            'isFavorite' => $this->resource['isFavorite'],
            'matchReason' => $this->resource['matchReason'],
            'uuid' => $this->resource['uuid'],
            'fullName' => $this->resource['fullName'],
            'firstName' => $this->resource['firstName'],
            'lastName' => $this->resource['lastName'],
            'age' => $this->resource['age'],
            'currentCity' => $this->resource['currentCity'],
            'currentState' => $this->resource['currentState'],
            'occupation' => $this->resource['occupation'],
            'profileImageUrl' => $this->resource['profileImageUrl'],
            'educationSummary' => $this->resource['educationSummary'],
            'profileVerificationStatus' => $this->resource['profileVerificationStatus'],
        ];
    }
}
