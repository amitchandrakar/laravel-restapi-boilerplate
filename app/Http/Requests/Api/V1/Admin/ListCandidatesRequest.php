<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\CandidateUserService;
use Illuminate\Validation\Rule;

class ListCandidatesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.candidates.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'bucket' => ['sometimes', 'string', Rule::in(CandidateUserService::LIST_BUCKETS)],
            'search' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', 'max:32'],
            'marital_status' => ['sometimes', 'string', 'max:64'],
            'profile_status' => ['sometimes', 'string', Rule::in(CandidateUserService::PROFILE_STATUSES)],
            'is_featured' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', Rule::in(['latest', 'oldest', 'name', 'published_at'])],
        ];
    }
}
