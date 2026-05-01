<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Reports\ActiveUsersReportRequest;
use App\Http\Requests\Api\V1\Reports\CandidateAreaReportRequest;
use App\Http\Requests\Api\V1\Reports\CandidateEducationReportRequest;
use App\Http\Requests\Api\V1\Reports\CandidateSurnameReportRequest;
use App\Http\Requests\Api\V1\Reports\TeamActivitiesReportRequest;
use App\Http\Requests\Api\V1\Reports\UserActivitiesReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function candidatesByArea(CandidateAreaReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.state.view')) {
            return $this->forbiddenResponse();
        }

        $groupBy = (string) $request->input('groupBy', 'district');
        $limit = (int) $request->integer('limit', 50);
        $data = $this->reportService->candidatesByArea($groupBy, $limit);

        return $this->successResponse($data, 'Candidate area report fetched successfully');
    }

    public function candidatesBySurname(CandidateSurnameReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.community.view')) {
            return $this->forbiddenResponse();
        }

        $limit = (int) $request->integer('limit', 50);
        $data = $this->reportService->candidatesBySurname($limit);

        return $this->successResponse($data, 'Candidate surname report fetched successfully');
    }

    public function candidatesByEducation(CandidateEducationReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.education.view')) {
            return $this->forbiddenResponse();
        }

        $limit = (int) $request->integer('limit', 50);
        $data = $this->reportService->candidatesByEducation($limit);

        return $this->successResponse($data, 'Candidate education report fetched successfully');
    }

    public function activeUsers(ActiveUsersReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.active_users.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = (int) $request->integer('perPage', 15);
        $paginator = $this->reportService->activeUsers($request->validated(), $perPage);

        return $this->paginatedResponse($paginator, 'Active users report fetched successfully');
    }

    public function userActivities(UserActivitiesReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.user_activities.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = (int) $request->integer('perPage', 15);
        $paginator = $this->reportService->userActivities($request->validated(), $perPage);

        return $this->paginatedResponse($paginator, 'User activities report fetched successfully');
    }

    public function teamActivities(TeamActivitiesReportRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.reports.team_activities.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = (int) $request->integer('perPage', 15);
        $paginator = $this->reportService->teamActivities($request->validated(), $perPage);

        return $this->paginatedResponse($paginator, 'Team activities report fetched successfully');
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.dashboard.view')) {
            return $this->forbiddenResponse();
        }

        $data = $this->reportService->dashboardStats();

        return $this->successResponse($data, 'Dashboard stats fetched successfully');
    }
}
