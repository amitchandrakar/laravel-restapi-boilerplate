<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\TeamUserService;
use Illuminate\Validation\Rule;

class ListTeamUsersRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.teams.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:255'],
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'status' => ['sometimes', 'string', 'max:32'],
            'gender' => ['sometimes', 'string', 'max:32'],
            'city' => ['sometimes', 'string', 'max:128'],
            'state' => ['sometimes', 'string', 'max:128'],
            'country' => ['sometimes', 'string', 'max:128'],
            'department' => ['sometimes', 'string', 'max:128'],
            'sort' => ['sometimes', 'string', Rule::in(TeamUserService::SORT_OPTIONS)],
        ];
    }
}
