<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin modules, permissions, and roles per docs/module_role_permission.md.
 * Uses the same guard as {@see config('auth.defaults.guard')} (typically `web`) for Spatie compatibility with Sanctum-authenticated users.
 */
class RbacSeeder extends Seeder
{
    public const GUARD = 'web';

    public function run(): void
    {
        $moduleRows = $this->moduleDefinitions();
        $moduleIds = [];

        foreach ($moduleRows as $row) {
            $module = Module::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'parent_id' => null,
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
            $moduleIds[$row['code']] = $module->id;
        }

        $permissionRows = $this->permissionDefinitions();

        foreach ($permissionRows as $row) {
            $moduleId = $moduleIds[$row['module_code']] ?? null;
            $perm = Permission::query()->firstOrNew([
                'name' => $row['name'],
                'guard_name' => self::GUARD,
            ]);

            if ($perm->uuid === null) {
                $perm->uuid = (string) Str::uuid();
            }
            $perm->fill([
                'module_id' => $moduleId,
                'action' => $row['action'],
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'is_active' => true,
            ]);
            $perm->save();
        }

        $allPermissionNames = collect($permissionRows)->pluck('name')->all();

        $admin = Role::query()->firstOrNew(['name' => 'admin', 'guard_name' => self::GUARD]);
        $admin->fill([
            'uuid' => $admin->uuid ?? (string) Str::uuid(),
            'title' => 'Administrator',
            'description' => 'Full access to all admin modules.',
            'is_system' => true,
            'is_default_registration' => false,
        ]);
        $admin->save();

        $reviewer = Role::query()->firstOrNew(['name' => 'reviewer', 'guard_name' => self::GUARD]);
        $reviewer->fill([
            'uuid' => $reviewer->uuid ?? (string) Str::uuid(),
            'title' => 'Reviewer',
            'description' => 'Staff reviewer with limited admin access.',
            'is_system' => true,
            'is_default_registration' => false,
        ]);
        $reviewer->save();

        $candidate = Role::query()->firstOrNew(['name' => 'candidate', 'guard_name' => self::GUARD]);
        $candidate->fill([
            'uuid' => $candidate->uuid ?? (string) Str::uuid(),
            'title' => 'Candidate',
            'description' => 'Default member role; includes editing own profile via admin section APIs.',
            'is_system' => true,
            'is_default_registration' => true,
        ]);
        $candidate->save();

        $admin->syncPermissions($allPermissionNames);

        $reviewer->syncPermissions($this->reviewerPermissionNames());

        $candidate->syncPermissions(['admin.candidates.edit']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<array{code: string, name: string, description?: string, sort_order: int}>
     */
    private function moduleDefinitions(): array
    {
        $n = 0;

        return [
            ['code' => 'admin_dashboard', 'name' => 'Admin — Dashboard', 'sort_order' => ++$n],
            ['code' => 'admin_candidates', 'name' => 'Admin — Candidates', 'sort_order' => ++$n],
            ['code' => 'admin_teams', 'name' => 'Admin — Teams', 'sort_order' => ++$n],
            ['code' => 'admin_users', 'name' => 'Admin — Users (API)', 'sort_order' => ++$n],
            ['code' => 'admin_packages', 'name' => 'Admin — Packages', 'sort_order' => ++$n],
            ['code' => 'admin_subscriptions', 'name' => 'Admin — Subscriptions', 'sort_order' => ++$n],
            ['code' => 'admin_payments', 'name' => 'Admin — Payments', 'sort_order' => ++$n],
            ['code' => 'admin_reports_state', 'name' => 'Admin — Reports (State)', 'sort_order' => ++$n],
            ['code' => 'admin_reports_community', 'name' => 'Admin — Reports (Community)', 'sort_order' => ++$n],
            ['code' => 'admin_reports_education', 'name' => 'Admin — Reports (Education)', 'sort_order' => ++$n],
            ['code' => 'admin_reports_active_users', 'name' => 'Admin — Reports (Active users)', 'sort_order' => ++$n],
            [
                'code' => 'admin_reports_user_activities',
                'name' => 'Admin — Reports (User activities)',
                'sort_order' => ++$n,
            ],
            [
                'code' => 'admin_reports_team_activities',
                'name' => 'Admin — Reports (Team activities)',
                'sort_order' => ++$n,
            ],
            ['code' => 'admin_settings_site', 'name' => 'Admin — Settings (Site)', 'sort_order' => ++$n],
            ['code' => 'admin_settings_payments', 'name' => 'Admin — Settings (Payments)', 'sort_order' => ++$n],
            ['code' => 'admin_settings_social', 'name' => 'Admin — Settings (Social login)', 'sort_order' => ++$n],
            [
                'code' => 'admin_settings_roles',
                'name' => 'Admin — Settings (Roles & permissions)',
                'sort_order' => ++$n,
            ],
            ['code' => 'admin_settings_seo', 'name' => 'Admin — Settings (SEO)', 'sort_order' => ++$n],
        ];
    }

    /**
     * @return list<array{name: string, module_code: string, action: string, title: string, description?: string}>
     */
    private function permissionDefinitions(): array
    {
        return array_merge(
            [
                [
                    'name' => 'admin.dashboard.view',
                    'module_code' => 'admin_dashboard',
                    'action' => 'view',
                    'title' => 'View dashboard',
                ],
                [
                    'name' => 'admin.candidates.view',
                    'module_code' => 'admin_candidates',
                    'action' => 'view',
                    'title' => 'View candidates',
                ],
                [
                    'name' => 'admin.candidates.add',
                    'module_code' => 'admin_candidates',
                    'action' => 'add',
                    'title' => 'Add candidates',
                ],
                [
                    'name' => 'admin.candidates.edit',
                    'module_code' => 'admin_candidates',
                    'action' => 'edit',
                    'title' => 'Edit candidates',
                ],
                [
                    'name' => 'admin.candidates.delete',
                    'module_code' => 'admin_candidates',
                    'action' => 'delete',
                    'title' => 'Delete candidates',
                ],
                [
                    'name' => 'admin.candidates.feature',
                    'module_code' => 'admin_candidates',
                    'action' => 'edit',
                    'title' => 'Mark candidates as featured',
                ],
                [
                    'name' => 'admin.candidates.impersonate',
                    'module_code' => 'admin_candidates',
                    'action' => 'edit',
                    'title' => 'Impersonate candidates',
                ],
                [
                    'name' => 'admin.candidates.export',
                    'module_code' => 'admin_candidates',
                    'action' => 'view',
                    'title' => 'Export candidates',
                ],
                [
                    'name' => 'admin.candidates.import',
                    'module_code' => 'admin_candidates',
                    'action' => 'add',
                    'title' => 'Import candidates',
                ],
                [
                    'name' => 'admin.teams.view',
                    'module_code' => 'admin_teams',
                    'action' => 'view',
                    'title' => 'View teams',
                ],
                [
                    'name' => 'admin.teams.add',
                    'module_code' => 'admin_teams',
                    'action' => 'add',
                    'title' => 'Add teams',
                ],
                [
                    'name' => 'admin.teams.edit',
                    'module_code' => 'admin_teams',
                    'action' => 'edit',
                    'title' => 'Edit teams',
                ],
                [
                    'name' => 'admin.teams.delete',
                    'module_code' => 'admin_teams',
                    'action' => 'delete',
                    'title' => 'Delete teams',
                ],
                [
                    'name' => 'admin.users.view',
                    'module_code' => 'admin_users',
                    'action' => 'view',
                    'title' => 'View users (API)',
                ],
                [
                    'name' => 'admin.users.add',
                    'module_code' => 'admin_users',
                    'action' => 'add',
                    'title' => 'Create users (API)',
                ],
                [
                    'name' => 'admin.users.edit',
                    'module_code' => 'admin_users',
                    'action' => 'edit',
                    'title' => 'Edit users (API)',
                ],
                [
                    'name' => 'admin.users.delete',
                    'module_code' => 'admin_users',
                    'action' => 'delete',
                    'title' => 'Delete users (API)',
                ],
                [
                    'name' => 'admin.packages.view',
                    'module_code' => 'admin_packages',
                    'action' => 'view',
                    'title' => 'View packages',
                ],
                [
                    'name' => 'admin.packages.add',
                    'module_code' => 'admin_packages',
                    'action' => 'add',
                    'title' => 'Add packages',
                ],
                [
                    'name' => 'admin.packages.edit',
                    'module_code' => 'admin_packages',
                    'action' => 'edit',
                    'title' => 'Edit packages',
                ],
                [
                    'name' => 'admin.packages.delete',
                    'module_code' => 'admin_packages',
                    'action' => 'delete',
                    'title' => 'Delete packages',
                ],
                [
                    'name' => 'admin.subscriptions.view',
                    'module_code' => 'admin_subscriptions',
                    'action' => 'view',
                    'title' => 'View subscriptions',
                ],
                [
                    'name' => 'admin.payments.view',
                    'module_code' => 'admin_payments',
                    'action' => 'view',
                    'title' => 'View payments',
                ],
                [
                    'name' => 'admin.payments.edit',
                    'module_code' => 'admin_payments',
                    'action' => 'edit',
                    'title' => 'Edit payments / approve',
                ],
                [
                    'name' => 'admin.payments.add',
                    'module_code' => 'admin_payments',
                    'action' => 'add',
                    'title' => 'Add payments',
                ],
                [
                    'name' => 'admin.payments.delete',
                    'module_code' => 'admin_payments',
                    'action' => 'delete',
                    'title' => 'Delete payments',
                ],
                [
                    'name' => 'admin.reports.state.view',
                    'module_code' => 'admin_reports_state',
                    'action' => 'view',
                    'title' => 'Reports: State',
                ],
                [
                    'name' => 'admin.reports.community.view',
                    'module_code' => 'admin_reports_community',
                    'action' => 'view',
                    'title' => 'Reports: Community',
                ],
                [
                    'name' => 'admin.reports.education.view',
                    'module_code' => 'admin_reports_education',
                    'action' => 'view',
                    'title' => 'Reports: Education',
                ],
                [
                    'name' => 'admin.reports.active_users.view',
                    'module_code' => 'admin_reports_active_users',
                    'action' => 'view',
                    'title' => 'Reports: Active users',
                ],
                [
                    'name' => 'admin.reports.user_activities.view',
                    'module_code' => 'admin_reports_user_activities',
                    'action' => 'view',
                    'title' => 'Reports: User activities',
                ],
                [
                    'name' => 'admin.reports.team_activities.view',
                    'module_code' => 'admin_reports_team_activities',
                    'action' => 'view',
                    'title' => 'Reports: Team activities',
                ],
                [
                    'name' => 'admin.settings.site.view',
                    'module_code' => 'admin_settings_site',
                    'action' => 'view',
                    'title' => 'View site settings',
                ],
                [
                    'name' => 'admin.settings.site.edit',
                    'module_code' => 'admin_settings_site',
                    'action' => 'edit',
                    'title' => 'Edit site settings',
                ],
                [
                    'name' => 'admin.settings.payments.view',
                    'module_code' => 'admin_settings_payments',
                    'action' => 'view',
                    'title' => 'View payment settings',
                ],
                [
                    'name' => 'admin.settings.payments.edit',
                    'module_code' => 'admin_settings_payments',
                    'action' => 'edit',
                    'title' => 'Edit payment settings',
                ],
                [
                    'name' => 'admin.settings.social.view',
                    'module_code' => 'admin_settings_social',
                    'action' => 'view',
                    'title' => 'View social login settings',
                ],
                [
                    'name' => 'admin.settings.social.edit',
                    'module_code' => 'admin_settings_social',
                    'action' => 'edit',
                    'title' => 'Edit social login settings',
                ],
                [
                    'name' => 'admin.settings.roles.view',
                    'module_code' => 'admin_settings_roles',
                    'action' => 'view',
                    'title' => 'View roles & permissions',
                ],
                [
                    'name' => 'admin.settings.roles.edit',
                    'module_code' => 'admin_settings_roles',
                    'action' => 'edit',
                    'title' => 'Edit roles & permissions',
                ],
                [
                    'name' => 'admin.settings.seo.view',
                    'module_code' => 'admin_settings_seo',
                    'action' => 'view',
                    'title' => 'View SEO settings',
                ],
                [
                    'name' => 'admin.settings.seo.edit',
                    'module_code' => 'admin_settings_seo',
                    'action' => 'edit',
                    'title' => 'Edit SEO settings',
                ],
            ],
            $this->candidateFeaturePermissionDefinitions()
        );
    }

    /**
     * @return list<string>
     */
    private function reviewerPermissionNames(): array
    {
        return [
            'admin.dashboard.view',
            'admin.candidates.view',
            'admin.candidates.add',
            'admin.candidates.edit',
            'admin.candidates.export',
            'admin.teams.view',
            'admin.packages.view',
            'admin.subscriptions.view',
            'admin.payments.view',
            'admin.payments.add',
            'admin.payments.edit',
            'admin.payments.delete',
            'admin.reports.state.view',
            'admin.reports.community.view',
            'admin.reports.education.view',
            'admin.reports.active_users.view',
            'admin.reports.user_activities.view',
            'admin.reports.team_activities.view',
            'admin.settings.site.view',
            'admin.settings.payments.view',
            'admin.settings.social.view',
            'admin.settings.seo.view',
        ];
    }

    /**
     * @return list<array{name: string, module_code: string, action: string, title: string, description?: string}>
     */
    private function candidateFeaturePermissionDefinitions(): array
    {
        return [
            [
                'name' => 'candidate.browse_profiles.limited',
                'module_code' => '',
                'action' => 'view',
                'title' => 'Browse profiles (limited view)',
            ],
            [
                'name' => 'candidate.browse_profiles.full',
                'module_code' => '',
                'action' => 'view',
                'title' => 'Browse profiles (full view)',
            ],
            [
                'name' => 'candidate.view_full_profile_details',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View full profile details',
            ],
            [
                'name' => 'candidate.view_profile_highlighting',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View profile highlighting',
            ],
            [
                'name' => 'candidate.view_instant_contact_access',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View instant contact access',
            ],
            [
                'name' => 'candidate.send_contact_requests',
                'module_code' => '',
                'action' => 'add',
                'title' => 'Send contact requests',
            ],
            [
                'name' => 'candidate.view_partner_preferences_details',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View partner preferences details',
            ],
            [
                'name' => 'candidate.view_lifestyle_details',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View lifestyle details',
            ],
            [
                'name' => 'candidate.view_contact_details',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View contact details',
            ],
            [
                'name' => 'candidate.mark_profiles_favorite',
                'module_code' => '',
                'action' => 'add',
                'title' => 'Mark profiles as favorite',
            ],
            [
                'name' => 'candidate.view_my_matches',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View my matches',
            ],
            [
                'name' => 'candidate.generate_kundali',
                'module_code' => '',
                'action' => 'add',
                'title' => 'Generate kundali',
            ],
            [
                'name' => 'candidate.view_kundali',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View kundali',
            ],
            [
                'name' => 'candidate.view_kundali_matching_results',
                'module_code' => '',
                'action' => 'view',
                'title' => 'View kundali matching results',
            ],
        ];
    }
}
