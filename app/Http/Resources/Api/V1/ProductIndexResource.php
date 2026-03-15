<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductIndexResource extends JsonResource
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
            'category' => $data['category'] ?? null,
            'delivery_area_count' => $data['delivery_area_count'] ?? null,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? null,
            'cafe_list' => $data['cafe_list'] ?? [],
            'dietary' => $data['dietary'] ?? null,
            'budget' => $data['budget'] ?? 0,
            'invitee_total' => $data['invitee_total'] ?? 0,
            'request_from_invitee' => $data['request_from_invitee'] ?? false,
            'go_config_exist' => $data['go_config_exist'] ?? false,
            'budget_active' => $data['budget_active'] ?? false,
            'dietaries' => $data['dietaries'] ?? [],
            'url' => $data['url'] ?? null,
        ];
    }
}
