<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Personal details + former "basics" fields (contact, marital status, photo URL, community fields).
 *
 * Admin `PUT|PATCH .../admin/candidates/{uuid}/sections/personal-details` requires core identity fields.
 * Candidate self-service `PATCH .../auth/candidate/profile/personal-details` allows partial updates.
 */
class SaveCandidatePersonalDetailsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return $this->isAdminCandidatePersonalDetailsRoute() ? $this->adminRules() : $this->candidateRules();
    }

    /**
     * @return array<string, mixed>
     */
    private function adminRules(): array
    {
        $targetUser = $this->resolveTargetUser();
        $ignoreId = $targetUser !== null ? $targetUser->id : $this->user()?->id;

        $emailRules = ['nullable', 'email', 'max:255'];
        $incomingEmail = mb_strtolower(trim((string) $this->input('email', '')));
        $currentEmail = mb_strtolower(trim((string) ($targetUser !== null ? $targetUser->email : '')));
        if ($incomingEmail !== '' && $incomingEmail !== $currentEmail) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($ignoreId, 'id')->whereNull('deleted_at');
        }

        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => $emailRules,
            'phone' => ['required', 'string', 'max:32'],
            'marital_status' => ['required', 'string', 'max:64'],
            'gender' => ['required', 'string', 'max:32'],
            'body_type' => ['nullable', 'string', 'max:64'],
            'complexion' => ['nullable', 'string', 'max:64'],
            'height' => ['required', 'string', 'max:32'],
            'blood_group' => ['nullable', 'string', 'max:8'],
            'manglik_status' => ['required', 'string', 'max:32'],
            'about_me' => ['required', 'string', 'max:500'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'sub_caste' => ['nullable', 'string', 'max:128'],
            'gotra' => ['nullable', 'string', 'max:128'],
            'rashi' => ['nullable', 'string', 'max:64'],
            'nakshatra' => ['nullable', 'string', 'max:64'],
            'occupation_id' => ['nullable', 'integer', Rule::exists('occupations', 'id')->where('is_active', true)],
            'income_range_id' => ['nullable', 'integer', Rule::exists('income_ranges', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateRules(): array
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
            'gender' => ['nullable', 'string', 'max:32'],
            'body_type' => ['nullable', 'string', 'max:64'],
            'complexion' => ['nullable', 'string', 'max:64'],
            'height' => ['nullable', 'string', 'max:32'],
            'blood_group' => ['nullable', 'string', 'max:8'],
            'manglik_status' => ['nullable', 'string', 'max:32'],
            'about_me' => ['nullable', 'string', 'max:500'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'sub_caste' => ['nullable', 'string', 'max:128'],
            'gotra' => ['nullable', 'string', 'max:128'],
            'rashi' => ['nullable', 'string', 'max:64'],
            'nakshatra' => ['nullable', 'string', 'max:64'],
            'occupation_id' => ['nullable', 'integer', Rule::exists('occupations', 'id')->where('is_active', true)],
            'income_range_id' => ['nullable', 'integer', Rule::exists('income_ranges', 'id')->where('is_active', true)],
        ];
    }

    private function isAdminCandidatePersonalDetailsRoute(): bool
    {
        $path = $this->path();

        return str_contains($path, 'admin/candidates/') && str_contains($path, 'sections/personal-details');
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
