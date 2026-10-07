<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Guest-safe browse card (teaser fields only).
 */
class PublicCandidateCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{user: User, profileImageUrl: string, educationSummary: string} $data */
        $data = $this->resource;
        $u = $data['user'];
        $age = $u->date_of_birth !== null ? $u->date_of_birth->age : null;

        return [
            'uuid' => $u->uuid,
            'fullName' => trim($u->first_name . ' ' . $u->last_name),
            'age' => $age,
            'profileImageUrl' => $data['profileImageUrl'],
            'educationSummary' => $data['educationSummary'],
            'profileAccess' => 'public',
        ];
    }
}
