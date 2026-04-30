<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', 'string', 'max:32'],
            'profile_photo_url' => ['nullable', 'url', 'max:2048'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query): void {
                    $query->whereIn('name', ['admin', 'reviewer']);
                }),
            ],
            'department' => ['required', 'string', 'max:128'],
            'job_title' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
