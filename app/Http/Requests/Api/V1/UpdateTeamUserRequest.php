<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeamUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $userId = (int) $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'id')->whereNull('deleted_at'),
            ],
            'phone' => ['sometimes', 'required', 'string', 'max:32'],
            'gender' => ['sometimes', 'required', 'string', 'max:32'],
            'profile_photo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query): void {
                    $query->whereIn('name', ['admin', 'reviewer']);
                }),
            ],
            'department' => ['sometimes', 'required', 'string', 'max:128'],
            'job_title' => ['sometimes', 'required', 'string', 'max:128'],
            'city' => ['sometimes', 'required', 'string', 'max:128'],
            'status' => ['sometimes', 'required', 'string', 'max:32'],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
        ];
    }
}
