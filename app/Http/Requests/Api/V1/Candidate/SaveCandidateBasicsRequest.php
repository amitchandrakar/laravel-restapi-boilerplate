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
        $targetUser = $this->resolveTargetUser();
        $ignoreId = $targetUser !== null ? $targetUser->id : $this->user()?->id;

        $emailRules = ['sometimes', 'required', 'email', 'max:255'];

        $incomingEmail = mb_strtolower(trim((string) $this->input('email', '')));
        $currentEmail = mb_strtolower(trim((string) ($targetUser !== null ? $targetUser->email : '')));

        if ($incomingEmail !== '' && $incomingEmail !== $currentEmail) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($ignoreId, 'id')->whereNull('deleted_at');
        }

        return [
            'first_name' => ['nullable', 'string', 'max:128'],
            'last_name' => ['nullable', 'string', 'max:128'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:32'],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'sub_caste' => ['nullable', 'string', 'max:128'],
        ];
    }

    private function resolveTargetUser(): ?User
    {
        $routeUser = $this->route('user');

        if ($routeUser instanceof User) {
            return $routeUser;
        }

        if (is_numeric($routeUser)) {
            return User::query()->find((int) $routeUser);
        }

        if (is_string($routeUser) && $routeUser !== '') {
            return User::query()->where('uuid', $routeUser)->first();
        }

        $authUser = $this->user();

        return $authUser instanceof User ? $authUser : null;
    }
}
