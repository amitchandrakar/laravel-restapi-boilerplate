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
            ->selectRaw("COALESCE(NULLIF(TRIM(ued.education_type), ''), 'unknown') as education, COUNT(DISTINCT ued.user_id) as total")
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

