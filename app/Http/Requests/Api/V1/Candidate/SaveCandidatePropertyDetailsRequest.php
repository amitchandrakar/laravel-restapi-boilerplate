<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SaveCandidatePropertyDetailsRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
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
            $p . 'properties' => ['nullable', 'array', 'max:20'],
            $p . 'properties.*.property_type' => [
                'nullable',
                'string',
                Rule::in(['petrol_pump', 'office', 'shop', 'agricultural_field', 'house', 'land', 'other']),
            ],
            $p . 'properties.*.area_sq_ft' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            $p . 'properties.*.city' => ['nullable', 'string', 'max:128'],
            $p . 'properties.*.state' => ['nullable', 'string', 'max:128'],
            $p . 'properties.*.country' => ['nullable', 'string', 'max:128'],
            $p . 'properties.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
