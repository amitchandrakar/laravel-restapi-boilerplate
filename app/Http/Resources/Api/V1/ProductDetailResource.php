<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
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
            'product' => $data['product'] ?? null,
            'item' => $data['item'] ?? null,
            'request_from_invitee' => $data['request_from_invitee'] ?? false,
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'cafe_list' => $data['cafe_list'] ?? [],
            'budget' => $data['budget'] ?? 0,
            'other_item_total_for_invitee' => $data['other_item_total_for_invitee'] ?? 0,
            'edit_item' => $data['edit_item'] ?? false,
            'invitee_total' => $data['invitee_total'] ?? 0,
            'go_config_exist' => $data['go_config_exist'] ?? false,
            'invitee_name' => $data['invitee_name'] ?? null,
            'budget_active' => $data['budget_active'] ?? false,
            'url' => $data['url'] ?? null,
        ];
    }
}
