<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardStats(): array
    {
        $candidateBaseQuery = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'candidate')
            ->whereNull('users.deleted_at');

        $totalCandidates = (int) (clone $candidateBaseQuery)->count();
        $newCandidates7Days = (int) (clone $candidateBaseQuery)
            ->where('users.created_at', '>=', now()->subDays(7))
            ->count();
        $newCandidates30Days = (int) (clone $candidateBaseQuery)
            ->where('users.created_at', '>=', now()->subDays(30))
            ->count();

        $premiumMembers = (int) DB::table('subscriptions')
            ->where('subscription_status', 'active')
            ->distinct('user_id')
            ->count('user_id');
        $freeMembers = max(0, $totalCandidates - $premiumMembers);

        $revenueDemo = (float) DB::table('payments')->where('payment_status', 'success')->sum('amount');

        $teamBaseQuery = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['admin', 'reviewer'])
            ->whereNull('users.deleted_at');
        $teamsCount = (int) (clone $teamBaseQuery)->count();

        $reportsGenerated7Days = (int) DB::table('user_activity_logs')
            ->where('activity_type', 'like', 'admin.reports.%')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $reportsGenerated30Days = (int) DB::table('user_activity_logs')
            ->where('activity_type', 'like', 'admin.reports.%')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $reportsGeneratedTotal = (int) DB::table('user_activity_logs')
            ->where('activity_type', 'like', 'admin.reports.%')
            ->count();

        $pendingApproval = (int) DB::table('user_verification_documents')
            ->where('verification_status', 'pending')
            ->count();
        $approvedToday = (int) DB::table('user_verification_documents')
            ->where('verification_status', 'approved')
            ->whereDate('verified_at', now()->toDateString())
            ->count();

        $activeMatchesTotal = (int) DB::table('matches')->where('match_status', 'active')->count();
        $profileViews7Days = (int) DB::table('profile_views')
            ->where('viewed_at', '>=', now()->subDays(7))
            ->count();
        $contactActionsTotal = (int) DB::table('contact_requests')->count();

        $successStories =
            (int) (DB::table('settings')
                ->where('group_key', 'metrics')
                ->whereIn('setting_key', ['success_stories_landing', 'success_stories_count'])
                ->value('setting_value') ?? 0);

        $maleCount = (int) (clone $candidateBaseQuery)->whereRaw('LOWER(users.gender) = ?', ['male'])->count();
        $femaleCount = (int) (clone $candidateBaseQuery)->whereRaw('LOWER(users.gender) = ?', ['female'])->count();
        $otherCount = max(0, $totalCandidates - $maleCount - $femaleCount);
        $genderSplit = [
            'male' => $maleCount,
            'female' => $femaleCount,
            'other' => $otherCount,
            'malePercent' => $totalCandidates > 0 ? round(($maleCount / $totalCandidates) * 100, 2) : 0.0,
            'femalePercent' => $totalCandidates > 0 ? round(($femaleCount / $totalCandidates) * 100, 2) : 0.0,
            'otherPercent' => $totalCandidates > 0 ? round(($otherCount / $totalCandidates) * 100, 2) : 0.0,
        ];

        $ageExpression =
            DB::getDriverName() === 'sqlite'
                ? "CAST((julianday('now') - julianday(users.date_of_birth)) / 365.25 AS INTEGER)"
                : 'TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE())';

        $candidatesByAge = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'candidate')
            ->whereNull('users.deleted_at')
            ->whereNotNull('users.date_of_birth')
            ->selectRaw($ageExpression . ' as age, COUNT(*) as total')
            ->groupBy('age')
            ->orderBy('age')
            ->get()
            ->map(static fn($row): array => ['age' => (int) $row->age, 'total' => (int) $row->total])
            ->values()
            ->all();

        $teamsByLocationRaw = (clone $teamBaseQuery)
            ->selectRaw("COALESCE(NULLIF(TRIM(users.current_city), ''), 'Other') as location, COUNT(*) as total")
            ->groupBy('location')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $teamsByLocationTotal = max(1, (int) $teamsByLocationRaw->sum('total'));
        $teamsByLocation = $teamsByLocationRaw
            ->map(
                static fn($row): array => [
                    'location' => (string) $row->location,
                    'total' => (int) $row->total,
                    'percent' => round(((int) $row->total / $teamsByLocationTotal) * 100, 2),
                ]
            )
            ->values()
            ->all();

        $topCommunities = (clone $candidateBaseQuery)
            ->selectRaw("COALESCE(NULLIF(TRIM(users.last_name), ''), 'Unknown') as community, COUNT(*) as total")
            ->groupBy('community')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(static fn($row): array => ['community' => (string) $row->community, 'total' => (int) $row->total])
            ->values()
            ->all();

        return [
            'totals' => [
                'candidates' => $totalCandidates,
                'newCandidates7Days' => $newCandidates7Days,
                'newCandidates30Days' => $newCandidates30Days,
                'premiumMembers' => $premiumMembers,
                'freeMembers' => $freeMembers,
                'revenueDemo' => $revenueDemo,
                'teams' => $teamsCount,
                'reportsGeneratedTotal' => $reportsGeneratedTotal,
                'reportsGenerated7Days' => $reportsGenerated7Days,
                'reportsGenerated30Days' => $reportsGenerated30Days,
                'pendingApproval' => $pendingApproval,
                'approvedToday' => $approvedToday,
                'activeMatchesTotal' => $activeMatchesTotal,
                'profileViews7Days' => $profileViews7Days,
                'contactActionsTotal' => $contactActionsTotal,
                'successStoriesLanding' => $successStories,
            ],
            'genderSplit' => $genderSplit,
            'candidatesByAge' => $candidatesByAge,
            'teamsByLocation' => $teamsByLocation,
            'topCommunities' => $topCommunities,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function candidatesByArea(string $groupBy = 'district', int $limit = 50): array
    {
        $column = match ($groupBy) {
            'state' => 'users.current_state',
            'city' => 'users.current_city',
            'village' => 'users.current_village',
            default => 'users.current_district',
        };

        $rows = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'candidate')
            ->whereNull('users.deleted_at')
            ->selectRaw("COALESCE(NULLIF(TRIM($column), ''), 'Unknown') as area, COUNT(*) as total")
            ->groupBy('area')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'groupBy' => $groupBy,
            'totalCandidates' => (int) DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.name', 'candidate')
                ->whereNull('users.deleted_at')
                ->count(),
            'buckets' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function candidatesBySurname(int $limit = 50): array
    {
        $rows = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'candidate')
            ->whereNull('users.deleted_at')
            ->selectRaw("COALESCE(NULLIF(TRIM(users.last_name), ''), 'Unknown') as surname, COUNT(*) as total")
            ->groupBy('surname')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'totalCandidates' => (int) DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.name', 'candidate')
                ->whereNull('users.deleted_at')
                ->count(),
            'buckets' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function candidatesByEducation(int $limit = 50): array
    {
        $rows = DB::table('user_education_details as ued')
            ->join('users', 'users.id', '=', 'ued.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'candidate')
            ->whereNull('users.deleted_at')
            ->selectRaw(
                "COALESCE(NULLIF(TRIM(ued.education_type), ''), 'unknown') as education, COUNT(DISTINCT ued.user_id) as total"
            )
            ->groupBy('education')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'buckets' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function activeUsers(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('user_sessions as us')
            ->join('users', 'users.id', '=', 'us.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->whereNull('users.deleted_at')
            ->where('us.is_active', true)
            ->select([
                'us.id',
                'us.uuid',
                'us.user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'roles.name as role',
                'us.login_at',
                'us.expires_at',
                'us.ip_address',
                'us.device_id',
            ])
            ->orderByDesc('us.login_at');

        if (!empty($filters['from'])) {
            $query->where('us.login_at', '>=', (string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('us.login_at', '<=', (string) $filters['to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function userActivities(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('user_activity_logs as ual')
            ->join('users', 'users.id', '=', 'ual.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->whereNull('users.deleted_at')
            ->select([
                'ual.id',
                'ual.uuid',
                'ual.user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'roles.name as role',
                'ual.activity_type',
                'ual.activity_source',
                'ual.metadata_json',
                'ual.ip_address',
                'ual.created_at',
            ])
            ->orderByDesc('ual.created_at');

        if (!empty($filters['userId'])) {
            $query->where('ual.user_id', (int) $filters['userId']);
        }
        if (!empty($filters['activityType'])) {
            $query->where('ual.activity_type', (string) $filters['activityType']);
        }
        if (!empty($filters['from'])) {
            $query->where('ual.created_at', '>=', (string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('ual.created_at', '<=', (string) $filters['to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function teamActivities(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('audit_logs as al')
            ->join('users', 'users.id', '=', 'al.actor_user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['admin', 'reviewer'])
            ->whereNull('users.deleted_at')
            ->select([
                'al.id',
                'al.uuid',
                'al.actor_user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'roles.name as role',
                'al.entity_type',
                'al.entity_id',
                'al.action',
                'al.old_values_json',
                'al.new_values_json',
                'al.ip_address',
                'al.created_at',
            ])
            ->orderByDesc('al.created_at');

        if (!empty($filters['actorUserId'])) {
            $query->where('al.actor_user_id', (int) $filters['actorUserId']);
        }
        if (!empty($filters['action'])) {
            $query->where('al.action', (string) $filters['action']);
        }
        if (!empty($filters['from'])) {
            $query->where('al.created_at', '>=', (string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('al.created_at', '<=', (string) $filters['to']);
        }

        return $query->paginate($perPage);
    }
}
