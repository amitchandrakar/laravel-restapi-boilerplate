<?php

declare(strict_types=1);

namespace App\Support\Postman;

use Illuminate\Support\Str;

final class PostmanRequestBuilder
{
    public const AUTH_TOKEN_VARIABLE = 'AUTH_TOKEN';

    public function __construct(
        private readonly PostmanModuleMapper $moduleMapper,
        private readonly PostmanFormRequestParser $formRequestParser,
        private readonly PostmanExampleResponses $exampleResponses
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(PostmanRouteRecord $record): array
    {
        $mapping = $this->moduleMapper->map($record);
        $name = $this->requestName($record);
        $headers = $this->headers($record, $mapping['realm']);
        $url = $this->url($record);
        $body = $this->body($record, $mapping['realm']);
        $description = $this->description($record, $mapping['realm']);

        $item = [
            'name' => $name,
            'request' => [
                'method' => $record->method,
                'header' => $headers,
                'url' => $url,
                'description' => $description,
                'auth' => $this->authConfig($record),
            ],
            'response' => $this->exampleResponses->forRoute($record, $name),
        ];

        if ($body !== null) {
            $item['request']['body'] = $body;
        }

        $events = $this->events($record);

        if ($events !== []) {
            $item['event'] = $events;
        }

        return $item;
    }

    private function requestName(PostmanRouteRecord $record): string
    {
        $suffix = $record->uri;

        if (str_starts_with($suffix, 'api/v1/admin/')) {
            $suffix = substr($suffix, strlen('api/v1/admin/'));
        } elseif (str_starts_with($suffix, 'api/v1/app/')) {
            $suffix = substr($suffix, strlen('api/v1/app/'));
        } elseif (str_starts_with($suffix, 'api/')) {
            $suffix = substr($suffix, strlen('api/'));
        }

        $label = strtoupper($record->method) . ' ' . $suffix;

        if ($record->controllerMethod !== null) {
            $label .= ' (' . $record->controllerMethod . ')';
        }

        return $label;
    }

    /**
     * @return list<array{key: string, value: string, type?: string}>
     */
    private function headers(PostmanRouteRecord $record, string $realm): array
    {
        $headers = [['key' => 'Accept', 'value' => 'application/json']];

        if ($this->isWebhook($record)) {
            $headers[] = ['key' => 'X-Razorpay-Signature', 'value' => '{{razorpay_webhook_signature}}'];

            return $headers;
        }

        if (
            $record->method !== 'GET' &&
            $record->method !== 'DELETE' &&
            !$this->formRequestParser->hasFileUpload($record->formRequestClass)
        ) {
            $headers[] = ['key' => 'Content-Type', 'value' => 'application/json'];
        }

        if ($realm === 'app' && str_contains($record->uri, 'api/v1/app/me/')) {
            $headers[] = ['key' => 'X-User-Profile-Uuid', 'value' => '{{candidate_uuid}}'];
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function url(PostmanRouteRecord $record): array
    {
        $segments = explode('/', $record->uri);
        $path = [];
        $variables = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{(.+)\}$/', $segment, $matches)) {
                $paramName = Str::before($matches[1], ':');
                $placeholder = $this->pathVariablePlaceholder($paramName);
                $path[] = ':' . $paramName;
                $variables[] = [
                    'key' => $paramName,
                    'value' => $placeholder,
                ];
            } else {
                $path[] = $segment;
            }
        }

        $raw = '{{BASE_URL}}/' . implode('/', $path);

        return [
            'raw' => $raw,
            'host' => ['{{BASE_URL}}'],
            'path' => $path,
            'variable' => $variables,
        ];
    }

    private function pathVariablePlaceholder(string $paramName): string
    {
        return match ($paramName) {
            'user', 'candidate' => '{{candidate_uuid}}',
            'package' => '{{package_uuid}}',
            'payment', 'paymentUuid' => '{{payment_uuid}}',
            'importId' => '{{import_batch_id}}',
            'document' => '{{document_uuid}}',
            'role' => '{{role_uuid}}',
            'notificationId' => '{{notification_id}}',
            'imageUuid' => '{{image_uuid}}',
            'contactRequest' => '{{contact_request_uuid}}',
            default => 'example-' . $paramName,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function body(PostmanRouteRecord $record, string $realm): ?array
    {
        if (!in_array($record->method, ['POST', 'PUT', 'PATCH'], true)) {
            return null;
        }

        if ($this->isLoginRoute($record)) {
            return $this->loginBody($realm);
        }

        if ($this->isWebhook($record)) {
            return [
                'mode' => 'raw',
                'raw' => json_encode(
                    [
                        'id' => 'evt_example_1',
                        'event' => 'payment.captured',
                        'payload' => [
                            'payment' => [
                                'entity' => [
                                    'id' => 'pay_example_1',
                                    'order_id' => 'order_example_1',
                                    'status' => 'captured',
                                ],
                            ],
                        ],
                    ],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ),
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        if ($this->formRequestParser->hasFileUpload($record->formRequestClass)) {
            return [
                'mode' => 'formdata',
                'formdata' => [
                    ['key' => 'file', 'type' => 'file', 'src' => []],
                    ['key' => 'type', 'value' => 'aadhaar', 'type' => 'text'],
                ],
            ];
        }

        $payload = $this->formRequestParser->examplePayload($record->formRequestClass);

        if ($payload === []) {
            $payload = (object) [];
        }

        return [
            'mode' => 'raw',
            'raw' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    private function isLoginRoute(PostmanRouteRecord $record): bool
    {
        return $record->method === 'POST' &&
            (str_ends_with($record->uri, 'auth/login') || str_contains($record->uri, '/auth/login'));
    }

    /**
     * @return array<string, mixed>
     */
    private function loginBody(string $realm): array
    {
        $payload =
            $realm === 'admin' || $realm === 'shared'
                ? [
                    'username' => '{{ADMIN_USERNAME}}',
                    'password' => '{{ADMIN_PASSWORD}}',
                ]
                : [
                    'username' => '{{CANDIDATE_USERNAME}}',
                    'password' => '{{CANDIDATE_PASSWORD}}',
                ];

        return [
            'mode' => 'raw',
            'raw' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function authConfig(PostmanRouteRecord $record): array
    {
        if ($this->isPublic($record)) {
            return ['type' => 'noauth'];
        }

        return [
            'type' => 'bearer',
            'bearer' => [
                [
                    'key' => 'token',
                    'value' => '{{' . self::AUTH_TOKEN_VARIABLE . '}}',
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(PostmanRouteRecord $record): array
    {
        if (!$this->isTokenIssuingRoute($record)) {
            return [];
        }

        return [
            [
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => $this->saveAuthTokenScriptLines(),
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function saveAuthTokenScriptLines(): array
    {
        return [
            'if (pm.response.code === 200 || pm.response.code === 201) {',
            '    const body = pm.response.json();',
            '    if (body.success && body.data && body.data.token) {',
            '        pm.environment.set(\'' . self::AUTH_TOKEN_VARIABLE . '\', body.data.token);',
            '        if (body.data.session_token_hash) {',
            '            pm.environment.set(\'session_token_hash\', body.data.session_token_hash);',
            '        }',
            '        if (body.data.user && body.data.user.uuid) {',
            '            pm.environment.set(\'candidate_uuid\', body.data.user.uuid);',
            '        }',
            '    }',
            '}',
        ];
    }

    private function isTokenIssuingRoute(PostmanRouteRecord $record): bool
    {
        $tokenPaths = ['auth/login', 'auth/register', 'auth/register-candidate', 'auth/refresh'];

        foreach ($tokenPaths as $path) {
            if (str_ends_with($record->uri, $path) || str_contains($record->uri, '/' . $path)) {
                return true;
            }
        }

        return false;
    }

    private function isPublic(PostmanRouteRecord $record): bool
    {
        if ($this->isWebhook($record)) {
            return true;
        }

        if (str_contains($record->uri, 'health') || $record->uri === 'api') {
            return true;
        }

        if (str_contains($record->uri, '/public/')) {
            return true;
        }

        $publicAuthPaths = [
            'auth/login',
            'auth/register',
            'auth/register-candidate',
            'auth/forgot-password',
            'auth/reset-password',
            'auth/registration',
        ];

        foreach ($publicAuthPaths as $path) {
            if (str_ends_with($record->uri, $path) || str_contains($record->uri, '/' . $path)) {
                return true;
            }
        }

        foreach ($record->middleware as $middleware) {
            if (str_starts_with($middleware, 'Authenticate')) {
                return false;
            }
        }

        return true;
    }

    private function isWebhook(PostmanRouteRecord $record): bool
    {
        return str_contains($record->uri, 'webhook') || str_contains($record->uri, 'razorpay/webhook');
    }

    private function description(PostmanRouteRecord $record, string $realm): string
    {
        $lines = ['**Controller:** `' . $record->action . '`', '**Realm:** ' . $realm];

        if (in_array('EnsureActiveTrackedSession', $record->middleware, true)) {
            $lines[] =
                '**Session:** Requires an active tracked session. Log in first; use the returned Bearer token. `session_token_hash` in the login response is for client reference — send `Authorization: Bearer {{' .
                self::AUTH_TOKEN_VARIABLE .
                '}}`.';
        }

        foreach ($record->middleware as $middleware) {
            if (str_starts_with($middleware, 'permission:')) {
                $lines[] = '**Permission:** `' . $middleware . '`';
            }
        }

        if ($record->formRequestClass !== null) {
            $lines[] = '**Form request:** `' . $record->formRequestClass . '`';
        }

        return implode("\n\n", $lines);
    }
}
