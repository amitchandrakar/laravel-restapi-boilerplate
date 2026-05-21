<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.teams.add') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'profile_photo_url' => ['nullable', 'url', 'max:2048'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query): void {
                    $query->whereIn('name', ['admin', 'reviewer']);
                }),
            ],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'city' => ['required', 'string', 'max:128'],
            'state' => ['nullable', 'string', 'max:128'],
            'country' => ['nullable', 'string', 'max:128'],
            'about' => ['nullable', 'string', 'max:5000'],
            'department' => ['nullable', 'string', 'max:128'],
            'job_title' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
