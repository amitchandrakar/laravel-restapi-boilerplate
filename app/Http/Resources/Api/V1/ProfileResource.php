<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'states' => $data['states'] ?? [],
            'delivery_area_count' => $data['delivery_area_count'] ?? 0,
            'delivery_area_chosen' => $data['delivery_area_chosen'] ?? false,
            'cafe_list' => $data['cafe_list'] ?? [],
        ];
    }
}
