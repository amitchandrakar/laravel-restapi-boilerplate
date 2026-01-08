<?php

if (!function_exists('api_response')) {
    /**
     * Return a standardized API response
     */
    function api_response(mixed $data = null, string $message = '', int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'success' => $code >= 200 && $code < 300,
                'message' => $message,
                'data' => $data,
            ],
            $code
        );
    }
}

if (!function_exists('api_error')) {
    /**
     * Return a standardized API error response
     */
    function api_error(string $message, int $code = 400, mixed $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}

if (!function_exists('api_success')) {
    /**
     * Return a standardized API success response
     */
    function api_success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): \Illuminate\Http\JsonResponse {
        return api_response($data, $message, $code);
    }
}

if (!function_exists('correlation_id')) {
    /**
     * Get or generate a correlation ID for request tracking
     */
    function correlation_id(): string
    {
        return request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
    }
}

if (!function_exists('get_locale')) {
    /**
     * Get the current locale
     */
    function get_locale(): string
    {
        return app()->getLocale();
    }
}

if (!function_exists('supported_locales')) {
    /**
     * Get array of supported locales
     */
    function supported_locales(): array
    {
        return explode(',', config('app.supported_locales', 'en'));
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format bytes to human-readable format
     */
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename for safe storage
     */
    function sanitize_filename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }
}

if (!function_exists('mask_email')) {
    /**
     * Mask email address for privacy
     */
    function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));

        return $maskedName . '@' . $domain;
    }
}

if (!function_exists('is_production')) {
    /**
     * Check if app is in production environment
     */
    function is_production(): bool
    {
        return app()->environment('production');
    }
}

if (!function_exists('is_local')) {
    /**
     * Check if app is in local environment
     */
    function is_local(): bool
    {
        return app()->environment('local');
    }
}

if (!function_exists('api_version')) {
    /**
     * Get current API version from config
     */
    function api_version(): string
    {
        return config('api.version', 'v1');
    }
}

if (!function_exists('generate_token_name')) {
    /**
     * Generate a token name with device info
     */
    function generate_token_name(string $prefix = 'api-token'): string
    {
        $agent = request()->userAgent();
        $ip = request()->ip();

        return $prefix . '-' . substr(md5($agent . $ip), 0, 8);
    }
}

if (!function_exists('array_to_snake_case')) {
    /**
     * Convert array keys to snake_case recursively
     */
    function array_to_snake_case(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $snakeKey = \Illuminate\Support\Str::snake($key);

            if (is_array($value)) {
                $result[$snakeKey] = array_to_snake_case($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('array_to_camel_case')) {
    /**
     * Convert array keys to camelCase recursively
     */
    function array_to_camel_case(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $camelKey = \Illuminate\Support\Str::camel($key);

            if (is_array($value)) {
                $result[$camelKey] = array_to_camel_case($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('route_exists')) {
    /**
     * Check if a named route exists
     */
    function route_exists(string $name): bool
    {
        try {
            route($name);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
