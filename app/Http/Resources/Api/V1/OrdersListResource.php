<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdersListResource extends JsonResource
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
            'active_orders' => $data['active_orders'] ?? [],
            'past_orders' => $data['past_orders'] ?? [],
            'individual_cart' => $data['individual_cart'] ?? null,
            'individual_cart_count' => $data['individual_cart_count'] ?? 0,
            'user_active_cart' => $data['user_active_cart'] ?? null,
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'cafe_list' => $data['cafe_list'] ?? [],
            'active_group_orders' => $data['active_group_orders'] ?? [],
        ];
    }
}
