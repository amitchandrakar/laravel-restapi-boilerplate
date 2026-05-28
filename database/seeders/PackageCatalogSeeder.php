<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $packages = [
            [
                'name' => 'Parichay',
                'code' => 'PARICHAY_FREE',
                'description' => 'Free: basic details (images, name, age, education, occupation). No full profile, no kundali, no partner preferences/lifestyle, contact details only by approved request, max 10 contact requests.',
                'duration_unit' => 'year',
                'monthly_price' => 0.0,
                'yearly_price' => 0.0,
                'is_default_registration' => true,
                'is_popular' => false,
                'currency' => 'INR',
                'sort_order' => 1,
            ],
            [
                'name' => 'Talash',
                'code' => 'TALASH_BASIC',
                'description' => 'Basic: includes free + profile attributes (height, weight, body type, complexion, etc). Contact via request/approval only. No kundali, no partner preferences/lifestyle, no match score, max 10 contact requests.',
                'duration_unit' => 'year',
                'monthly_price' => 40.0,
                'yearly_price' => 365.0,
                'is_default_registration' => false,
                'is_popular' => true,
                'currency' => 'INR',
                'sort_order' => 2,
            ],
            [
                'name' => 'Rishta',
                'code' => 'RISHTA_PRO',
                'description' => 'Pro: full profile access including kundali matching, partner preferences, lifestyle details, match score percentage, and unlimited contact requests.',
                'duration_unit' => 'year',
                'monthly_price' => 75.0,
                'yearly_price' => 730.0,
                'is_default_registration' => false,
                'is_popular' => false,
                'currency' => 'INR',
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $pkg) {
            $existingId = (int) DB::table('packages')->where('code', $pkg['code'])->value('id');

            if ($existingId === 0) {
                DB::table('packages')->insert([
                    'uuid' => (string) Str::uuid(),
                    'name' => $pkg['name'],
                    'code' => $pkg['code'],
                    'description' => $pkg['description'],
                    'duration_unit' => $pkg['duration_unit'],
                    'monthly_price' => $pkg['monthly_price'],
                    'yearly_price' => $pkg['yearly_price'],
                    'price' => $pkg['yearly_price'],
                    'discounted_price' => null,
                    'currency' => $pkg['currency'],
                    'is_active' => true,
                    'is_default_registration' => (bool) $pkg['is_default_registration'],
                    'is_popular' => (bool) $pkg['is_popular'],
                    'sort_order' => $pkg['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertedId = (int) DB::table('packages')->where('code', $pkg['code'])->value('id');
                $this->syncPackagePermissions($insertedId, (string) $pkg['code']);

                continue;
            }

            DB::table('packages')
                ->where('id', $existingId)
                ->update([
                    'name' => $pkg['name'],
                    'description' => $pkg['description'],
                    'duration_unit' => $pkg['duration_unit'],
                    'monthly_price' => $pkg['monthly_price'],
                    'yearly_price' => $pkg['yearly_price'],
                    'price' => $pkg['yearly_price'],
                    'discounted_price' => null,
                    'currency' => $pkg['currency'],
                    'is_active' => true,
                    'is_default_registration' => (bool) $pkg['is_default_registration'],
                    'is_popular' => (bool) $pkg['is_popular'],
                    'sort_order' => $pkg['sort_order'],
                    'updated_at' => $now,
                ]);

            $this->syncPackagePermissions((int) $existingId, (string) $pkg['code']);
        }
    }

    /**
     * @return list<string>
     */
    private function talashPermissionNames(): array
    {
        return [
            'candidate.browse_profiles.full',
            'candidate.view_full_profile_details',
            'candidate.send_contact_requests',
            'candidate.mark_profiles_favorite',
            'candidate.view_partner_preferences_details',
            'candidate.view_lifestyle_details',
        ];
    }

    /**
     * @return list<string>
     */
    private function rishtaPermissionNames(): array
    {
        return array_values(
            array_unique([
                ...$this->talashPermissionNames(),
                'candidate.browse_profiles.limited',
                'candidate.view_my_matches',
                'candidate.view_profile_highlighting',
                'candidate.view_instant_contact_access',
                'candidate.view_contact_details',
                'candidate.generate_kundali',
                'candidate.view_kundali',
                'candidate.view_kundali_matching_results',
            ])
        );
    }

    private function syncPackagePermissions(int $packageId, string $packageCode): void
    {
        $permissionNames = match (strtoupper($packageCode)) {
            'PARICHAY_FREE' => ['candidate.browse_profiles.limited'],
            'TALASH_BASIC' => $this->talashPermissionNames(),
            'RISHTA_PRO' => $this->rishtaPermissionNames(),
            default => [],
        };

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->map(static fn($id): int => (int) $id)
            ->all();

        DB::table('package_permissions')->where('package_id', $packageId)->delete();

        foreach ($permissionIds as $permissionId) {
            DB::table('package_permissions')->insert([
                'package_id' => $packageId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
