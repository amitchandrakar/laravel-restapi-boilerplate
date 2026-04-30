<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class SaveCandidateBasicsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $ignoreId = null;
        if ($routeUser instanceof User) {
            $ignoreId = (int) $routeUser->id;
        } elseif (is_numeric($routeUser)) {
            $ignoreId = (int) $routeUser;
        } elseif (is_string($routeUser) && $routeUser !== '') {
            $ignoreId = (int) User::query()->where('uuid', $routeUser)->value('id');
        }

        return [
            'profile_slug' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreId, 'id')->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'religion' => ['nullable', 'string', 'max:128'],
            'caste' => ['nullable', 'string', 'max:128'],
            'sub_caste' => ['nullable', 'string', 'max:128'],
            'community' => ['nullable', 'string', 'max:128'],
        ];
    }
}
