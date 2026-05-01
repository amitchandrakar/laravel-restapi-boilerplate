<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PublicFeaturedCandidateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $photoUrl = DB::table('user_images')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_profile_photo', true)
            ->orderBy('sort_order')
            ->value('image_url');

        if ($photoUrl === null) {
            $photoUrl = DB::table('user_images')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('image_url');
        }

        $age = $user->date_of_birth !== null ? now()->diffInYears($user->date_of_birth) : null;

        return [
            'uuid' => $user->uuid,
            'profileSlug' => data_get($user, 'profile_slug'),
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'photoUrl' => $photoUrl,
            'currentCity' => $user->current_city,
            'currentState' => $user->current_state,
            'age' => $age,
        ];
    }
}
