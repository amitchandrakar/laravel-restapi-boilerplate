<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\UserPushDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Firebase Cloud Messaging HTTP v1 sender (Android + iOS via FCM tokens).
 */
class FcmPushService
{
    /**
     * @param  array<int, mixed>  $tokens
     * @param  array<string, mixed>  $data  Stringifiable key/value data payload for deep links
     *
     * @return array{sent: int, failed: int, skipped: bool, reason?: string}
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(
            array_unique(
                array_filter(
                    array_map(static fn($t): string => is_string($t) ? trim($t) : '', $tokens),
                    static fn(string $t): bool => $t !== ''
                )
            )
        );

        if ($tokens === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true, 'reason' => 'no_tokens'];
        }

        if (!$this->pushEnabled()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true, 'reason' => 'push_disabled'];
        }

        try {
            $projectId = $this->projectId();
            $accessToken = $this->accessToken();
        } catch (Throwable $e) {
            Log::warning('FcmPushService: credentials unavailable', [
                'message' => $e->getMessage(),
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => true, 'reason' => 'credentials_missing'];
        }

        $sent = 0;
        $failed = 0;
        $stringData = [];

        foreach ($data as $key => $value) {
            if ($key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $stringData[$key] = (string) ($value ?? '');
            }
        }

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(15)
                    ->post(
                        'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send',
                        $payload
                    );

                if ($response->successful()) {
                    $sent++;
                } else {
                    $failed++;
                    Log::warning('FcmPushService: send failed', [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ]);

                    if ($this->isInvalidTokenResponse($response->json())) {
                        UserPushDevice::query()->where('token_hash', hash('sha256', $token))->delete();
                    }
                }
            } catch (Throwable $e) {
                $failed++;
                Log::warning('FcmPushService: send exception', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => false];
    }

    public function pushEnabled(): bool
    {
        try {
            $settings = NotificationSetting::query()->first();

            if ($settings instanceof NotificationSetting) {
                return $settings->push_enabled;
            }
        } catch (Throwable) {
            // table may be missing in early migrate
        }

        return (bool) config('firebase.push_enabled', true);
    }

    /**
     * @throws RuntimeException
     */
    public function projectId(): string
    {
        $credentials = $this->serviceAccount();
        $projectId = (string) ($credentials['project_id'] ?? (config('firebase.project_id') ?? ''));

        if ($projectId === '') {
            throw new RuntimeException('Firebase project_id is missing.');
        }

        return $projectId;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function serviceAccount(): array
    {
        $path = (string) config('firebase.credentials');

        if ($path !== '' && is_readable($path)) {
            $json = file_get_contents($path);
            $decoded = is_string($json) ? json_decode($json, true) : null;

            if (is_array($decoded) && isset($decoded['client_email'], $decoded['private_key'])) {
                return $decoded;
            }
        }

        $inline = (string) config('firebase.credentials_json');

        if ($inline !== '') {
            $decoded = json_decode($inline, true);

            if (is_array($decoded) && isset($decoded['client_email'], $decoded['private_key'])) {
                return $decoded;
            }
        }

        throw new RuntimeException('Firebase service account credentials are not configured.');
    }

    /**
     * @throws RuntimeException
     */
    private function accessToken(): string
    {
        $credentials = $this->serviceAccount();
        $cacheKey = 'firebase_fcm_access_token_' . md5((string) ($credentials['client_email'] ?? ''));

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $jwtClaim = $this->base64UrlEncode(
            json_encode(
                [
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $unsigned = $jwtHeader . '.' . $jwtClaim;
        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('Invalid Firebase private key.');
        }

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$ok) {
            throw new RuntimeException('Unable to sign Firebase JWT.');
        }

        $assertion = $unsigned . '.' . $this->base64UrlEncode($signature);

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Firebase OAuth token request failed: ' . $response->body());
        }

        $accessToken = (string) ($response->json('access_token') ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Firebase OAuth response missing access_token.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600) - 60);
        Cache::put($cacheKey, $accessToken, $expiresIn);

        return $accessToken;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function isInvalidTokenResponse(mixed $json): bool
    {
        if (!is_array($json)) {
            return false;
        }

        $status = (string) (data_get($json, 'error.status') ?? '');
        $message = strtolower((string) (data_get($json, 'error.message') ?? ''));

        return $status === 'NOT_FOUND' ||
            str_contains($message, 'not a valid fcm') ||
            str_contains($message, 'requested entity was not found') ||
            str_contains($message, 'registration-token-not-registered');
    }
}
