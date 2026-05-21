<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeamUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.teams.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? (int) $routeUser->id : (int) $routeUser;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:128'],
            'last_name' => ['sometimes', 'required', 'string', 'max:128'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'id')->whereNull('deleted_at'),
            ],
            'phone' => ['sometimes', 'required', 'string', 'max:32'],
            'gender' => ['sometimes', 'required', 'string', Rule::in(['male', 'female', 'other'])],
            'profile_photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'profile_photo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query): void {
                    $query->whereIn('name', ['admin', 'reviewer']);
                }),
            ],
            'permission_ids' => ['sometimes', 'nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'city' => ['sometimes', 'required', 'string', 'max:128'],
            'state' => ['sometimes', 'nullable', 'string', 'max:128'],
            'country' => ['sometimes', 'nullable', 'string', 'max:128'],
            'about' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'department' => ['sometimes', 'nullable', 'string', 'max:128'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:128'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
        ];
    }
}
