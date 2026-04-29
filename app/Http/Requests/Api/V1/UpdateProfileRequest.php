<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Identity check is enforced in controller (optional user_id/userId must match auth user)
            'user_id' => 'sometimes',
            'userId' => 'sometimes',

            'firstName' => 'sometimes|string|max:255',
            'lastName' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique($this->user()->getTable(), 'email')->ignore($this->user()->id),
            ],
            'secondaryEmail' => 'sometimes|nullable|string|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'secondaryPhone' => 'sometimes|nullable|string|max:50',
            'company' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'address2' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'state' => 'sometimes|nullable|string|max:255',
            'zip' => 'sometimes|nullable|string|max:20',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'firstName.string' => 'The first name must be a valid string',
            'lastName.string' => 'The last name must be a valid string',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already taken',
            'secondaryEmail.email' => 'Please provide a valid secondary email address',
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'firstName' => 'first name',
            'lastName' => 'last name',
            'email' => 'email address',
            'secondaryEmail' => 'secondary email address',
            'phone' => 'phone',
            'secondaryPhone' => 'secondary phone',
            'company' => 'company',
            'address' => 'address',
            'address2' => 'address 2',
            'city' => 'city',
            'state' => 'state',
            'zip' => 'zip',
        ];
    }
}
