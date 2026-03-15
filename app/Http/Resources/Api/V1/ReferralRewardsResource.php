<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralRewardsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'user' => $data['user'] ?? null,
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'allow_refer' => $data['allow_refer'] ?? false,
            'referral_config' => $data['referral_config'] ?? null,
            'refered_emails' => $data['refered_emails'] ?? [],
        ];
    }
}
