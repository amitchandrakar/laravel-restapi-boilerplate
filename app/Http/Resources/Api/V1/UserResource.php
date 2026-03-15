<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'dob' => $user->dob ?? null,
            'company_name' => $user->company_name ?? null,
            'salary' => $user->salary ?? null,
            'contact_number' => $user->contact_number ?? null,
            'status' => $user->status ?? null,
            'account_type' => $user->account_type ?? null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
