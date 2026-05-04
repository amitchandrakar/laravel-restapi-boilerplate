<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\AdminCandidateProfileDetailsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin read-only candidate payload with resolved geography and master-data labels.
 *
 * @mixin User
 */
class AdminCandidateProfileDetailsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return app(AdminCandidateProfileDetailsService::class)->buildForCandidate($user);
    }
}
