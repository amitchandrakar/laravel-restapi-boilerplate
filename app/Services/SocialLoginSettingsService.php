<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\SocialLoginSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class SocialLoginSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return SocialLoginSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::SocialLogin;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
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
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['google_live_client_secret', 'facebook_live_client_secret', 'instagram_live_client_secret'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var SocialLoginSetting $record */
        $flags = $maskSecrets ? $this->secretFlags($record) : [];

        return array_merge(
            [
                'googleEnabled' => $record->google_enabled,
                'googleEnvironment' => $record->google_environment ?? 'live',
                'googleLiveClientId' => $record->google_live_client_id,
                'googleLiveRedirectUrl' => $record->google_live_redirect_url,
                'facebookEnabled' => $record->facebook_enabled,
                'facebookEnvironment' => $record->facebook_environment ?? 'live',
                'facebookLiveClientId' => $record->facebook_live_client_id,
                'facebookLiveRedirectUrl' => $record->facebook_live_redirect_url,
                'instagramEnabled' => $record->instagram_enabled,
                'instagramEnvironment' => $record->instagram_environment ?? 'live',
                'instagramLiveClientId' => $record->instagram_live_client_id,
                'instagramLiveRedirectUrl' => $record->instagram_live_redirect_url,
            ],
            $flags,
            $maskSecrets
                ? []
                : [
                    'googleLiveClientSecret' => $record->google_live_client_secret,
                    'facebookLiveClientSecret' => $record->facebook_live_client_secret,
                    'instagramLiveClientSecret' => $record->instagram_live_client_secret,
                ]
        );
    }
}
