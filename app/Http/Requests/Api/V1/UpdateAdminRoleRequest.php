<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends ApiFormRequest
{
    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
            ],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_default_registration' => ['sometimes', 'boolean'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Role|null $role */
            $role = $this->route('role');

            if (!$role instanceof Role) {
                return;
            }

            if ($role->is_system && $this->filled('name') && (string) $this->input('name') !== (string) $role->name) {
                $validator->errors()->add('name', 'System role name cannot be changed.');
            }
        });
    }
}
