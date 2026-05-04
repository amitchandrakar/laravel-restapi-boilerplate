<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SaveCandidateFamilyBackgroundRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return self::rulesWithPrefix('');
    }

    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Contracts\Validation\Rule>>
     */
    public static function rulesWithPrefix(string $prefix): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.') . '.';

        return [
            $p . 'father_name' => ['nullable', 'string', 'max:255'],
            $p . 'father_occupation' => ['nullable', 'string', 'max:255'],
            $p . 'father_gotra' => ['nullable', 'string', 'max:128'],
            $p . 'father_native_place' => ['nullable', 'string', 'max:255'],
            $p . 'mother_name' => ['nullable', 'string', 'max:255'],
            $p . 'mother_occupation' => ['nullable', 'string', 'max:255'],
            $p . 'mother_gotra' => ['nullable', 'string', 'max:128'],
            $p . 'mother_native_place' => ['nullable', 'string', 'max:255'],
            $p . 'brothers_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            $p . 'sisters_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            $p . 'family_type' => ['nullable', 'string', 'max:64'],
            $p . 'family_status' => ['nullable', 'string', 'max:64'],
            $p . 'siblings' => ['nullable', 'array', 'max:20'],
            $p . 'siblings.*.name' => ['required', 'string', 'max:255'],
            $p . 'siblings.*.gender' => ['nullable', 'string', 'max:32'],
            $p . 'siblings.*.relation_type' => ['nullable', 'string', Rule::in(['brother', 'sister'])],
            $p . 'siblings.*.marital_status' => ['nullable', 'string', 'max:64'],
            $p . 'siblings.*.occupation' => ['nullable', 'string', 'max:255'],
            $p . 'siblings.*.education' => ['nullable', 'string', 'max:255'],
            $p . 'siblings.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            $p . 'siblings.*.is_elder' => ['nullable', 'boolean'],
            $p . 'siblings.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
