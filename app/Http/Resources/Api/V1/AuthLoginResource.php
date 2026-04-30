<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];
        $user = $data['user'] ?? null;
        if ($user instanceof JsonResource) {
            $user = $user->resolve();
        }

        return [
            'user' => $user,
            'token' => $data['token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'permissions' => $data['permissions'] ?? [],
        ];
    }
}
