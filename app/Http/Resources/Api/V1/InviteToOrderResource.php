<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteToOrderResource extends JsonResource
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
            'group_order_id' => $data['group_order_id'] ?? null,
            'group_orders' => $data['group_orders'] ?? null,
            'delivery_times' => $data['delivery_times'] ?? [],
            'invitee_response_times' => $data['invitee_response_times'] ?? [],
            'invitee_products' => $data['invitee_products'] ?? [],
            'invitee_budget' => $data['invitee_budget'] ?? [],
            'min_product_price' => $data['min_product_price'] ?? 0,
            'max_default_budget' => $data['max_default_budget'] ?? 0,
            'max_product_price' => $data['max_product_price'] ?? 0,
            'config_data' => $data['config_data'] ?? null,
            'shipping_data' => $data['shipping_data'] ?? null,
            'states' => $data['states'] ?? [],
            'previous_group' => $data['previous_group'] ?? false,
            'allow_weekend_orders' => $data['allow_weekend_orders'] ?? false,
        ];
    }
}
