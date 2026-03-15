<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponseBuilder
{
    /**
     * Machine-readable error codes for API responses.
     */
    public const ERROR_NOT_FOUND = 'NOT_FOUND';

    public const ERROR_VALIDATION = 'VALIDATION_ERROR';

    public const ERROR_UNAUTHORIZED = 'UNAUTHORIZED';

    public const ERROR_FORBIDDEN = 'FORBIDDEN';

    public const ERROR_METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';

    public const ERROR_TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';

    public const ERROR_CONFLICT = 'CONFLICT';

    public const ERROR_DB_ERROR = 'DB_ERROR';

    public const ERROR_INTERNAL = 'INTERNAL_SERVER_ERROR';

    public const ERROR_BAD_REQUEST = 'BAD_REQUEST';

    public const ERROR_GENERIC = 'ERROR';

    /**
     * Build the base meta block (timestamp, requestId, version).
     */
    public static function baseMeta(?array $extra = null): array
    {
        $requestId = request()->attributes->get('request_id');
        if ($requestId === null) {
            $requestId = 'req_' . \Illuminate\Support\Str::ulid();
        }

        $meta = [
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'requestId' => $requestId,
            'version' => config('api.response.version', config('api.version', '1.0.0')),
        ];

        if (is_array($extra) && !empty($extra)) {
            $meta = array_merge($meta, $extra);
        }

        return $meta;
    }

    /**
     * Build pagination block from a LengthAwarePaginator (for single list in meta or co-located).
     */
    public static function paginationFromPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'total' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'hasNext' => $paginator->hasMorePages(),
            'hasPrev' => $paginator->currentPage() > 1,
        ];
    }

    /**
     * Build a list object with items and pagination (for multiple paginated lists in data).
     */
    public static function paginatedList(LengthAwarePaginator|JsonResource $paginator): array
    {
        if ($paginator instanceof JsonResource) {
            $paginator = $paginator->resource;
        }
        if (!$paginator instanceof LengthAwarePaginator) {
            throw new \InvalidArgumentException('Pagination data not found.');
        }
        $items = $paginator->items();

        return [
            'items' => $items,
            'pagination' => self::paginationFromPaginator($paginator),
        ];
    }

    /**
     * Build the full response envelope.
     *
     * @param  array{code?: string, details?: string, field?: string|null, fields?: array}  $error
     * @param  array{string, mixed}  $metaExtra
     */
    public static function envelope(
        bool $success,
        int $statusCode,
        string $message,
        mixed $data = null,
        ?array $error = null,
        ?array $metaExtra = null
    ): array {
        $payload = [
            'success' => $success,
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data,
            'error' => $error,
            'meta' => self::baseMeta($metaExtra),
        ];

        return $payload;
    }

    /**
     * Build a success JSON response with envelope.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        ?array $metaExtra = null
    ): JsonResponse {
        $envelope = self::envelope(true, $statusCode, $message, $data, null, $metaExtra);

        return response()->json($envelope, $statusCode);
    }

    /**
     * Build an error JSON response with envelope.
     *
     * @param  array{field: string, message: string}[]|null  $fields
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        ?string $errorCode = null,
        ?string $details = null,
        ?array $fields = null
    ): JsonResponse {
        $error = [
            'code' => $errorCode ?? self::ERROR_GENERIC,
            'details' => $details ?? $message,
            'field' => null,
        ];
        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        $envelope = self::envelope(false, $statusCode, $message, null, $error, null);

        return response()->json($envelope, $statusCode);
    }

    /**
     * Transform Laravel validation errors to { field, message }[].
     *
     * @param  array<string, array<int, string>|string>  $errors
     * @return array<int, array{field: string, message: string}>
     */
    public static function normalizeValidationFields(array $errors): array
    {
        $normalized = [];
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $message = $messages[0] ?? 'Invalid';
            } else {
                $message = (string) $messages;
            }
            $normalized[] = ['field' => $field, 'message' => $message];
        }

        return $normalized;
    }
}
