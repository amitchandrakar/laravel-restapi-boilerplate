<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ListCandidateDiscoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
            'gender' => ['nullable', 'string', 'max:32'],
            'min_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'max_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'community' => ['nullable', 'array'],
            'community.*' => ['integer', 'exists:surnames,id'],
            'city' => ['nullable', 'string', 'max:128'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'education' => ['nullable', 'array'],
            'education.*' => ['integer', 'exists:degrees,id'],
            'occupation' => ['nullable', 'array'],
            'occupation.*' => ['integer', 'exists:occupations,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeOptionalScalars();
        foreach (['education', 'occupation', 'community'] as $key) {
            $this->mergeOptionalIdList($key);
        }
    }

    private function mergeOptionalScalars(): void
    {
        $gender = $this->input('gender');
        if (is_string($gender) && trim($gender) === '') {
            $this->merge(['gender' => null]);
        }

        $city = $this->input('city');
        if (is_string($city) && trim($city) === '') {
            $this->merge(['city' => null]);
        }

        foreach (['city_id', 'min_age', 'max_age'] as $key) {
            $v = $this->input($key);
            if ($v === '' || $v === null) {
                $this->merge([$key => null]);

                continue;
            }
            if (!is_numeric($v)) {
                $this->merge([$key => null]);

                continue;
            }
            $n = (int) $v;
            if ($n <= 0) {
                $this->merge([$key => null]);

                continue;
            }
            $this->merge([$key => $n]);
        }

        $perPage = $this->input('perPage');
        if ($perPage === '' || $perPage === null || !is_numeric($perPage)) {
            $this->merge(['perPage' => 15]);
        } else {
            $this->merge(['perPage' => max(1, min(50, (int) $perPage))]);
        }
    }

    /**
     * Accepts comma-separated strings (preferred), a single numeric value, or an array (e.g. legacy `key[]=`).
     * Empty tokens, blank placeholders, and non-integer values are dropped; an all-empty input clears the filter.
     */
    private function mergeOptionalIdList(string $key): void
    {
        $raw = $this->input($key);
        if ($raw === null || $raw === '') {
            $this->merge([$key => null]);

            return;
        }

        $tokens = [];
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if ($item === null || $item === '') {
                    continue;
                }
                if (is_int($item)) {
                    $tokens[] = $item;

                    continue;
                }
                if (is_string($item)) {
                    foreach (explode(',', $item) as $piece) {
                        $piece = trim($piece);
                        if ($piece !== '') {
                            $tokens[] = $piece;
                        }
                    }
                }
            }
        } elseif (is_numeric($raw)) {
            $tokens = [(int) $raw];
        } elseif (is_string($raw)) {
            foreach (explode(',', $raw) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $tokens[] = $piece;
                }
            }
        } else {
            $this->merge([$key => null]);

            return;
        }

        $ids = [];
        foreach ($tokens as $token) {
            if (is_int($token)) {
                if ($token > 0) {
                    $ids[] = $token;
                }

                continue;
            }
            if (filter_var($token, FILTER_VALIDATE_INT) !== false) {
                $v = (int) $token;
                if ($v > 0) {
                    $ids[] = $v;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        $this->merge([$key => $ids === [] ? null : $ids]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $min = $this->input('min_age');
            $max = $this->input('max_age');
            if ($min !== null && $max !== null && (int) $max < (int) $min) {
                $v->errors()->add('max_age', 'The max age must be greater than or equal to min age.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $data = $this->validated();

        return array_filter(
            [
                'gender' => $data['gender'] ?? null,
                'min_age' => isset($data['min_age']) ? (int) $data['min_age'] : null,
                'max_age' => isset($data['max_age']) ? (int) $data['max_age'] : null,
                'community' => isset($data['community']) ? array_values(array_map('intval', $data['community'])) : null,
                'city' => $data['city'] ?? null,
                'city_id' => isset($data['city_id']) ? (int) $data['city_id'] : null,
                'education' => isset($data['education']) ? array_values(array_map('intval', $data['education'])) : null,
                'occupation' => isset($data['occupation'])
                    ? array_values(array_map('intval', $data['occupation']))
                    : null,
            ],
            static function (mixed $v): bool {
                if ($v === null || $v === '') {
                    return false;
                }
                if (is_array($v) && $v === []) {
                    return false;
                }

                return true;
            }
        );
    }
}
