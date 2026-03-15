<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRewardsResource extends JsonResource
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
            'cashout_amount' => $data['cashout_amount'] ?? null,
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'partial_amount' => $data['partial_amount'] ?? [],
            'reward_config' => $data['reward_config'] ?? false,
            'reward_email' => $data['reward_email'] ?? null,
            'rewards' => $data['rewards'] ?? [],
        ];
    }
}
