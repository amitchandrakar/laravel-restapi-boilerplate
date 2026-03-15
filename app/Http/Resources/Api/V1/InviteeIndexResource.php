<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteeIndexResource extends JsonResource
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
            'categories' => $data['categories'] ?? [],
            'budget' => $data['budget'] ?? 0,
            'invitee_total' => $data['invitee_total'] ?? 0,
            'go_config_exist' => $data['go_config_exist'] ?? false,
            'invitee_name_exist' => $data['invitee_name_exist'] ?? false,
            'budget_active' => $data['budget_active'] ?? false,
            'individual_cart' => $data['individual_cart'] ?? null,
            'cart_info' => $data['cart_info'] ?? null,
        ];
    }
}
