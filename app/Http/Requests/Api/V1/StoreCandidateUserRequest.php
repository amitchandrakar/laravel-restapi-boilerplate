<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\CandidateUserService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCandidateUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.candidates.add') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required_without_all:first_name,last_name', 'string', 'max:255'],
            'first_name' => ['sometimes', 'required_without:name', 'string', 'max:128'],
            'last_name' => ['sometimes', 'string', 'max:128'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'string', 'max:32'],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'height' => ['nullable', 'string', 'max:32'],
            'weight' => ['nullable', 'string', 'max:32'],
            'blood_group' => ['nullable', 'string', 'max:8'],
            'body_type' => ['nullable', 'string', 'max:64'],
            'about_me' => ['nullable', 'string', 'max:5000'],
            'profile_status' => ['sometimes', 'string', Rule::in(CandidateUserService::PROFILE_STATUSES)],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
        ];
    }
}
