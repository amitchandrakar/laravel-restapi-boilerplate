<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterCandidateRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if ($email === null || (is_string($email) && trim($email) === '')) {
            $this->merge(['email' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => [
                'required',
                'string',
                'max:128',
                Rule::exists('surnames', 'name')->where('is_active', true),
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'gender' => ['required', 'string', 'max:32'],
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                'before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
                'after:' . now()->subYears(120)->format('Y-m-d'),
            ],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'package_uuid' => [
                'required',
                'string',
                'uuid',
                Rule::exists('packages', 'uuid')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }
}
