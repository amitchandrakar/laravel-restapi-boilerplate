<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;

class ResetPasswordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        if ($this->user() instanceof User) {
            return [
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed|different:current_password',
            ];
        }

        return [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Please provide your current password',
            'password.required' => 'The new password field is required',
            'password.min' => 'New password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'password.different' => 'New password must be different from current password',
        ];
    }
}
