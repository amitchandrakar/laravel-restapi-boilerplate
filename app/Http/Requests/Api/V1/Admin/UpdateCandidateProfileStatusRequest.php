<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\CandidateUserService;
use Illuminate\Validation\Rule;

class UpdateCandidateProfileStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.candidates.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profile_status' => ['required', 'string', Rule::in(CandidateUserService::PROFILE_STATUSES)],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
