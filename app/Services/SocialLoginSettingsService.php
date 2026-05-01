<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocialLoginSettingsService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        $map = [
            'googleEnabled' => 'google_enabled',
            'googleEnvironment' => 'google_environment',
            'googleLiveClientId' => 'google_live_client_id',
            'googleLiveClientSecret' => 'google_live_client_secret',
            'googleLiveRedirectUrl' => 'google_live_redirect_url',

            'facebookEnabled' => 'facebook_enabled',
            'facebookEnvironment' => 'facebook_environment',
            'facebookLiveClientId' => 'facebook_live_client_id',
            'facebookLiveClientSecret' => 'facebook_live_client_secret',
            'facebookLiveRedirectUrl' => 'facebook_live_redirect_url',

            'instagramEnabled' => 'instagram_enabled',
            'instagramEnvironment' => 'instagram_environment',
            'instagramLiveClientId' => 'instagram_live_client_id',
            'instagramLiveClientSecret' => 'instagram_live_client_secret',
            'instagramLiveRedirectUrl' => 'instagram_live_redirect_url',
        ];

        DB::transaction(function () use ($data, $map): void {
            foreach ($map as $payloadKey => $settingKey) {
                if (!array_key_exists($payloadKey, $data)) {
                    continue;
                }

                $value = $data[$payloadKey];
                $valueType = is_bool($value) ? 'boolean' : 'string';
                $storedValue = is_bool($value) ? ($value ? '1' : '0') : ($value !== null ? (string) $value : null);

                DB::table('settings')->updateOrInsert(
                    ['group_key' => 'social_login', 'setting_key' => $settingKey],
                    [
                        'uuid' => (string) Str::uuid(),
                        'setting_value' => $storedValue,
                        'value_type' => $valueType,
                        'is_public' => false,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return $this->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $rows = DB::table('settings')->where('group_key', 'social_login')->get();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row->setting_key] = (string) ($row->setting_value ?? '');
            if ((string) $row->value_type === 'boolean') {
                $indexed[(string) $row->setting_key] = (string) $row->setting_value === '1';
            }
        }

        return [
            'googleEnabled' => (bool) ($indexed['google_enabled'] ?? false),
            'googleEnvironment' => $indexed['google_environment'] ?? 'live',
            'googleLiveClientId' => $indexed['google_live_client_id'] ?? null,
            'googleLiveClientSecret' => $indexed['google_live_client_secret'] ?? null,
            'googleLiveRedirectUrl' => $indexed['google_live_redirect_url'] ?? null,

            'facebookEnabled' => (bool) ($indexed['facebook_enabled'] ?? false),
            'facebookEnvironment' => $indexed['facebook_environment'] ?? 'live',
            'facebookLiveClientId' => $indexed['facebook_live_client_id'] ?? null,
            'facebookLiveClientSecret' => $indexed['facebook_live_client_secret'] ?? null,
            'facebookLiveRedirectUrl' => $indexed['facebook_live_redirect_url'] ?? null,

            'instagramEnabled' => (bool) ($indexed['instagram_enabled'] ?? false),
            'instagramEnvironment' => $indexed['instagram_environment'] ?? 'live',
            'instagramLiveClientId' => $indexed['instagram_live_client_id'] ?? null,
            'instagramLiveClientSecret' => $indexed['instagram_live_client_secret'] ?? null,
            'instagramLiveRedirectUrl' => $indexed['instagram_live_redirect_url'] ?? null,
        ];
    }
}
