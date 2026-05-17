<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Support\UserImageStorageUrl;
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
        $defaultPhotoUrl = (string) config('custom.image.profile_default', '/images/Coming-Soon.png');

        $photoUrl = DB::table('user_images')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('is_profile_photo', true)
            ->orderBy('sort_order')
            ->value('image_url');

        if ($photoUrl === null) {
            $photoUrl = DB::table('user_images')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->value('image_url');
        }
        if (!is_string($photoUrl) || $photoUrl === '') {
            $photoUrl = $defaultPhotoUrl;
        } else {
            $photoUrl =
                UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($photoUrl) ?? $photoUrl) ??
                $photoUrl;
        }

        $age = $user->date_of_birth !== null ? $user->date_of_birth->age : null;
        $dateOfBirth = $user->date_of_birth !== null ? (string) $user->date_of_birth : null;

        $matchPercentage = null;
        $scoreByUserId = $request->attributes->get('matchScoreByUserId');
        if (is_array($scoreByUserId)) {
            $raw = $scoreByUserId[(int) $user->id] ?? null;
            $matchPercentage = $raw === null ? null : max(0, min(100, (int) $raw));
        }

        return [
            'uuid' => $user->uuid,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'photoUrl' => $photoUrl,
            'currentCity' => $user->current_city,
            'currentState' => $user->current_state,
            'age' => $age,
            'dateOfBirth' => $dateOfBirth,
            'matchPercentage' => $matchPercentage,
        ];
    }
}
