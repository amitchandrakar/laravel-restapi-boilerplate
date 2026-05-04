<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiCalls
{
    private const MAX_RESPONSE_CHARS = 4000;

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'otp',
        'secret',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $requestId = (string) $request->attributes->get('request_id', '');
        $userId = $request->user()?->id;
        $context = [
            'request_id' => $requestId !== '' ? $requestId : null,
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'query' => $request->query(),
            'payload' => $this->maskSensitive($request->all()),
        ];
        $context['request_json'] = $this->toPrettyJson([
            'request_id' => $context['request_id'],
            'user_id' => $context['user_id'],
            'method' => $context['method'],
            'path' => $context['path'],
            'full_url' => $context['full_url'],
            'ip' => $context['ip'],
            'user_agent' => $context['user_agent'],
            'query' => $context['query'],
            'payload' => $context['payload'],
        ]);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            Log::warning(
                'api.call.failed',
                array_merge($context, [
                    'duration_ms' => $this->durationMs($start),
                    'status_code' => $this->statusFromThrowable($e),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ])
            );

            throw $e;
        }

        Log::info(
            'api.call.completed',
            array_merge($context, [
                'duration_ms' => $this->durationMs($start),
                'status_code' => $response->getStatusCode(),
                'response' => $this->responseSummary($response),
                'response_json' => $this->toPrettyJson($this->responseSummary($response)),
            ])
        );

        return $response;
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            $keyName = is_string($key) ? $key : (string) $key;
            if (in_array(strtolower($keyName), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***';

                continue;
            }

            if ($value instanceof UploadedFile) {
                $data[$key] = '[uploaded file: ' . $value->getClientOriginalName() . ']';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            }
        }

        return $data;
    }

    private function durationMs(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }

    private function statusFromThrowable(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            $status = $e->getStatusCode();
            if (is_int($status)) {
                return $status;
            }
        }

        return 500;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSummary(Response $response): array
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        $raw = $response->getContent();
        if (!is_string($raw) || $raw === '') {
            return [
                'content_type' => $contentType,
                'body' => null,
            ];
        }

        $body = $this->summarizeBody($raw);

        return [
            'content_type' => $contentType,
            'body' => $body,
        ];
    }

    /**
     * @return array<string, mixed>|string
     */
    private function summarizeBody(string $raw): array|string
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->maskSensitive($decoded);
        }

        return mb_strlen($raw) > self::MAX_RESPONSE_CHARS
            ? mb_substr($raw, 0, self::MAX_RESPONSE_CHARS) . '...[truncated]'
            : $raw;
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $data
     */
    private function toPrettyJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? $json : '{}';
    }
}
