<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartSummaryResource extends JsonResource
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
            'group' => $data['group'] ?? null,
            'cart_info' => $data['cart_info'] ?? null,
            'group_detail' => $data['group_detail'] ?? null,
            'items' => $data['items'] ?? [],
            'display_wc_banner' => $data['display_wc_banner'] ?? false,
            'warm_cookie_data' => $data['warm_cookie_data'] ?? null,
            'item_count' => $data['item_count'] ?? null,
            'item_count_invitee' => $data['item_count_invitee'] ?? null,
            'owner_count' => $data['owner_count'] ?? null,
            'delivery_area_count' => $data['delivery_area_count'] ?? null,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? null,
            'cafe_list' => $data['cafe_list'] ?? null,
            'budget' => $data['budget'] ?? null,
            'request_from_invitee' => $data['request_from_invitee'] ?? null,
            'go_config_exist' => $data['go_config_exist'] ?? null,
            'allow_leader_to_send_reminder_email' => $data['allow_leader_to_send_reminder_email'] ?? null,
            'budget_active' => $data['budget_active'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'pending_invitees' => $data['pending_invitees'] ?? null,
            'invitee_total' => $data['invitee_total'] ?? null,
        ];
    }
}
