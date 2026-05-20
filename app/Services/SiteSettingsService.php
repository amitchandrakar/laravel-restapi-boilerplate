<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteSettingsService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        $map = [
            'siteName' => 'site_name',
            'logoUrl' => 'logo_url',
            'faviconUrl' => 'favicon_url',
            'allowedCommunitySurnames' => 'allowed_community_surnames',
            'maintenanceMode' => 'maintenance_mode',
            'requireProfileApproval' => 'require_profile_approval',
        ];

        DB::transaction(function () use ($data, $map): void {
            foreach ($map as $payloadKey => $settingKey) {
                if (!array_key_exists($payloadKey, $data)) {
                    continue;
                }

                $value = $data[$payloadKey];
                $valueType = 'string';

                if (is_bool($value)) {
                    $valueType = 'boolean';
                    $value = $value ? '1' : '0';
                } elseif (is_array($value)) {
                    $valueType = 'json';
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                } else {
                    $value = $value !== null ? (string) $value : null;
                }

                DB::table('settings')->updateOrInsert(
                    ['group_key' => 'site', 'setting_key' => $settingKey],
                    [
                        'uuid' => (string) Str::uuid(),
                        'setting_value' => $value,
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
        $rows = DB::table('settings')->where('group_key', 'site')->get();
        $indexed = [];

        foreach ($rows as $row) {
            $key = (string) $row->setting_key;
            $indexed[$key] = match ((string) $row->value_type) {
                'boolean' => (string) $row->setting_value === '1',
                'json' => json_decode((string) $row->setting_value, true, 512, JSON_THROW_ON_ERROR),
                default => $row->setting_value,
            };
        }

        return [
            'siteName' => $indexed['site_name'] ?? null,
            'logoUrl' => $indexed['logo_url'] ?? null,
            'faviconUrl' => $indexed['favicon_url'] ?? null,
            'allowedCommunitySurnames' => is_array($indexed['allowed_community_surnames'] ?? null)
                ? $indexed['allowed_community_surnames']
                : [],
            'maintenanceMode' => (bool) ($indexed['maintenance_mode'] ?? false),
            'requireProfileApproval' => (bool) ($indexed['require_profile_approval'] ?? false),
        ];
    }
}
