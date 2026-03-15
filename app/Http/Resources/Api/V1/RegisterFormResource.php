<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterFormResource extends JsonResource
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
            'industries' => $data['industries'] ?? [],
            'states' => $data['states'] ?? [],
            'display_reward_greet' => $data['display_reward_greet'] ?? false,
            'display_referral_reward_greet' => $data['display_referral_reward_greet'] ?? false,
            'referral_config' => $data['referral_config'] ?? null,
            'to_email' => $data['to_email'] ?? '',
        ];
    }
}
