<?php

declare(strict_types=1);

namespace App\Support\Postman;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class PostmanFormRequestParser
{
    /**
     * @return array<string, mixed>
     */
    public function examplePayload(?string $formRequestClass): array
    {
        if ($formRequestClass === null || !class_exists($formRequestClass)) {
            return [];
        }

        try {
            /** @var FormRequest $request */
            $request = new $formRequestClass();
            $rules = $request->rules();
        } catch (\Throwable) {
            return [];
        }

        return $this->buildFromRules($rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     *
     * @return array<string, mixed>
     */
    private function buildFromRules(array $rules): array
    {
        $payload = [];

        foreach ($rules as $field => $ruleSet) {
            if (!is_string($field)) {
                continue;
            }

            if (str_contains($field, '.')) {
                $root = Str::before($field, '.');

                if (!array_key_exists($root, $payload)) {
                    $payload[$root] = $this->guessValueForField($root, ['array']);
                }

                continue;
            }

            $normalized = $this->normalizeRules($ruleSet);

            if (
                str_contains(
                    implode('|', array_map(static fn($r): string => is_string($r) ? $r : '', $normalized)),
                    'confirmed'
                )
            ) {
                $payload[$field] = $this->guessValueForField($field, $normalized);
                $payload[$field . '_confirmation'] = $payload[$field];

                continue;
            }

            $payload[$field] = $this->guessValueForField($field, $normalized);
        }

        return $payload;
    }

    /**
     * @return list<string|object>
     */
    private function normalizeRules(mixed $ruleSet): array
    {
        if (is_string($ruleSet)) {
            return explode('|', $ruleSet);
        }

        if (!is_array($ruleSet)) {
            return [];
        }

        $normalized = [];

        foreach ($ruleSet as $rule) {
            if ($rule instanceof Rule) {
                $normalized[] = 'rule_object';

                continue;
            }

            $normalized[] = is_string($rule) ? $rule : 'mixed';
        }

        return $normalized;
    }

    /**
     * @param  list<string|object>  $rules
     */
    private function guessValueForField(string $field, array $rules): mixed
    {
        $joined = implode('|', array_map(static fn($r): string => is_string($r) ? $r : 'object', $rules));

        if (str_contains($joined, 'boolean')) {
            return true;
        }

        if (str_contains($joined, 'integer') || str_contains($joined, 'numeric')) {
            return 1;
        }

        if (str_contains($joined, 'array')) {
            return [];
        }

        if ($field === 'username') {
            return 'user@example.com';
        }

        if (str_contains($joined, 'email')) {
            return 'user@example.com';
        }

        if (str_contains($joined, 'uuid')) {
            return $this->envPlaceholderForField($field);
        }

        if (str_contains($joined, 'date')) {
            return '1995-06-15';
        }

        if (str_contains($joined, 'password')) {
            return 'Password@example1';
        }

        if (str_contains($joined, 'file') || str_contains($joined, 'image')) {
            return null;
        }

        if (str_contains($joined, 'in:')) {
            if (preg_match('/in:([^|]+)/', $joined, $matches)) {
                $options = explode(',', $matches[1]);

                return trim((string) ($options[0] ?? 'example'));
            }
        }

        if (str_contains($field, 'phone')) {
            return '9876543210';
        }

        if ($field === 'name' || str_ends_with($field, '_name') || $field === 'title') {
            return 'Example Name';
        }

        if (str_contains($field, 'description') || str_contains($field, 'message') || str_contains($field, 'notes')) {
            return 'Example description text.';
        }

        return 'example';
    }

    private function envPlaceholderForField(string $field): string
    {
        return match (true) {
            str_contains($field, 'package') => '{{package_uuid}}',
            str_contains($field, 'payment') => '{{payment_uuid}}',
            str_contains($field, 'import') => '{{import_batch_id}}',
            str_contains($field, 'notification') => '{{notification_id}}',
            str_contains($field, 'image') => '{{image_uuid}}',
            str_contains($field, 'document') => '{{document_uuid}}',
            str_contains($field, 'role') => '{{role_uuid}}',
            str_contains($field, 'contact') => '{{contact_request_uuid}}',
            default => '{{candidate_uuid}}',
        };
    }

    public function hasFileUpload(?string $formRequestClass): bool
    {
        if ($formRequestClass === null || !class_exists($formRequestClass)) {
            return false;
        }

        try {
            /** @var FormRequest $request */
            $request = new $formRequestClass();
            $rules = $request->rules();
        } catch (\Throwable) {
            return false;
        }

        foreach ($rules as $ruleSet) {
            $normalized = $this->normalizeRules($ruleSet);
            $joined = implode('|', array_map(static fn($r): string => is_string($r) ? $r : '', $normalized));

            if (str_contains($joined, 'file') || str_contains($joined, 'image')) {
                return true;
            }
        }

        return false;
    }
}
