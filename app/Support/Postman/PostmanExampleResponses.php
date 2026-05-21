<?php

declare(strict_types=1);

namespace App\Support\Postman;

use App\Support\ApiResponseBuilder;

final class PostmanExampleResponses
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forRoute(PostmanRouteRecord $record, string $requestName): array
    {
        $usesSession = in_array('EnsureActiveTrackedSession', $record->middleware, true);
        $successStatus = $this->successStatus($record);
        $successBody = $this->successBody($record, $requestName, $successStatus);

        return [
            $this->example('Success', $successStatus, $successBody),
            $this->example('Bad Request', 400, $this->errorBody(ApiResponseBuilder::ERROR_BAD_REQUEST, 'Bad request')),
            $this->example(
                'Unauthorized',
                401,
                $this->errorBody(ApiResponseBuilder::ERROR_UNAUTHORIZED, 'Unauthorized')
            ),
            $this->example(
                'Forbidden',
                403,
                $this->errorBody(
                    $usesSession ? ApiResponseBuilder::ERROR_SESSION_INVALID : ApiResponseBuilder::ERROR_FORBIDDEN,
                    $usesSession ? 'Session is no longer active. Please sign in again.' : 'Forbidden',
                    $usesSession ? 'Tracked session missing or expired.' : 'Forbidden'
                )
            ),
            $this->example(
                'Not Found',
                404,
                $this->errorBody(ApiResponseBuilder::ERROR_NOT_FOUND, 'Resource not found')
            ),
            $this->example(
                'Validation Error',
                422,
                $this->errorBody(ApiResponseBuilder::ERROR_VALIDATION, 'Validation failed.', 'Validation failed.', [
                    ['field' => 'email', 'message' => 'The email field is required.'],
                ])
            ),
            $this->example(
                'Too Many Requests',
                429,
                $this->errorBody(ApiResponseBuilder::ERROR_TOO_MANY_REQUESTS, 'Too many requests')
            ),
            $this->example(
                'Internal Server Error',
                500,
                $this->errorBody(ApiResponseBuilder::ERROR_INTERNAL, 'Internal server error')
            ),
        ];
    }

    private function successStatus(PostmanRouteRecord $record): int
    {
        return match ($record->method) {
            'POST' => str_contains($record->uri, 'login') ||
            str_contains($record->uri, 'register') ||
            str_contains($record->uri, 'import') ||
            str_contains($record->uri, 'checkout')
                ? 201
                : 200,
            'DELETE' => 200,
            default => 200,
        };
    }

    /**
     * @return array<string, mixed>|string
     */
    private function successBody(PostmanRouteRecord $record, string $requestName, int $status): array|string
    {
        if (str_ends_with($record->uri, 'candidates/export') && $record->method === 'GET') {
            return "uuid,email,first_name,last_name,profile_status\n{{candidate_uuid}},candidate@example.com,Riya,Chandrakar,published\n";
        }

        if (str_contains($record->uri, 'health')) {
            return ApiResponseBuilder::envelope(true, 200, 'OK', ['status' => 'ok'], null, null);
        }

        if (str_contains($record->uri, 'login')) {
            return ApiResponseBuilder::envelope(
                true,
                200,
                'Login successful',
                [
                    'token' => '1|plainTextSanctumTokenExample',
                    'token_type' => 'Bearer',
                    'session_token_hash' => 'sha256_hash_of_plain_token',
                    'user' => [
                        'uuid' => '{{candidate_uuid}}',
                        'email' => 'admin@example.com',
                        'name' => 'Admin User',
                    ],
                    'permissions' => ['admin.candidates.view'],
                ],
                null,
                null
            );
        }

        if (str_contains($record->uri, 'register')) {
            return ApiResponseBuilder::envelope(
                true,
                $status,
                'Registered successfully',
                [
                    'token' => '1|plainTextSanctumTokenExample',
                    'token_type' => 'Bearer',
                    'session_token_hash' => 'sha256_hash_of_plain_token',
                    'user' => [
                        'uuid' => '{{candidate_uuid}}',
                        'email' => 'user@example.com',
                    ],
                ],
                null,
                null
            );
        }

        if ($record->method === 'DELETE') {
            return ApiResponseBuilder::envelope(true, 200, 'Deleted successfully', null, null, null);
        }

        if (str_contains($record->uri, 'candidates') && $record->method === 'GET' && !str_contains($record->uri, '{')) {
            return ApiResponseBuilder::envelope(
                true,
                200,
                'Success',
                [
                    'items' => [
                        [
                            'uuid' => '{{candidate_uuid}}',
                            'email' => 'candidate@example.com',
                            'profileStatus' => 'published',
                        ],
                    ],
                    'pagination' => [
                        'page' => 1,
                        'limit' => 20,
                        'total' => 1,
                        'totalPages' => 1,
                        'hasNext' => false,
                        'hasPrev' => false,
                    ],
                ],
                null,
                null
            );
        }

        return ApiResponseBuilder::envelope(true, $status, 'Success', ['request' => $requestName], null, null);
    }

    /**
     * @param  array<int, array{field: string, message: string}>|null  $fields
     *
     * @return array<string, mixed>
     */
    private function errorBody(string $code, string $message, ?string $details = null, ?array $fields = null): array
    {
        $error = [
            'code' => $code,
            'details' => $details ?? $message,
            'field' => null,
        ];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return ApiResponseBuilder::envelope(false, $this->statusForCode($code), $message, null, $error, null);
    }

    private function statusForCode(string $code): int
    {
        return match ($code) {
            ApiResponseBuilder::ERROR_UNAUTHORIZED => 401,
            ApiResponseBuilder::ERROR_FORBIDDEN, ApiResponseBuilder::ERROR_SESSION_INVALID => 403,
            ApiResponseBuilder::ERROR_NOT_FOUND => 404,
            ApiResponseBuilder::ERROR_VALIDATION => 422,
            ApiResponseBuilder::ERROR_TOO_MANY_REQUESTS => 429,
            ApiResponseBuilder::ERROR_INTERNAL => 500,
            default => 400,
        };
    }

    /**
     * @param  array<string, mixed>|string  $body
     *
     * @return array<string, mixed>
     */
    private function example(string $name, int $status, array|string $body): array
    {
        $isCsv = is_string($body);

        return [
            'name' => $name,
            'originalRequest' => [
                'method' => 'GET',
                'header' => [],
                'url' => [
                    'raw' => '{{base_url}}/',
                    'host' => ['{{base_url}}'],
                    'path' => [''],
                ],
            ],
            'status' => (string) $status,
            'code' => $status,
            '_postman_previewlanguage' => $isCsv ? 'plain' : 'json',
            'header' => [
                [
                    'key' => 'Content-Type',
                    'value' => $isCsv ? 'text/csv' : 'application/json',
                ],
            ],
            'body' => $isCsv ? $body : json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }
}
