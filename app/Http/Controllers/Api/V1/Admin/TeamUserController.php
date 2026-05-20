<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\StoreTeamUserRequest;
use App\Http\Requests\Api\V1\UpdateTeamUserRequest;
use App\Http\Resources\Api\V1\TeamUserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\TeamUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamUserController extends Controller
{
    public function __construct(private readonly TeamUserService $service) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.teams.view')) {
            return $this->forbiddenResponse();
        }
        $paginator = $this->service->list((int) $request->integer('perPage', 15));

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.teams.index',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->paginatedResponse(TeamUserResource::collection($paginator), 'Team users fetched successfully');
    }

    public function store(StoreTeamUserRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.teams.add')) {
            return $this->forbiddenResponse();
        }
        $user = $this->service->create($request->validated());

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $user->id,
            'create',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.teams.create',
            'api_v1_admin',
            ['user_id' => $user->id],
            $request->ip()
        );

        return $this->createdResponse(TeamUserResource::make($user), 'Team user created successfully');
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.teams.view')) {
            return $this->forbiddenResponse();
        }
        $teamUser = User::query()->teamUsers()->where('id', $user->id)->first();

        if (!($teamUser instanceof User)) {
            return $this->notFoundResponse('Team user not found');
        }

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.teams.show',
            'api_v1_admin',
            ['user_id' => $teamUser->id],
            $request->ip()
        );

        return $this->successResponse(TeamUserResource::make($teamUser), 'Team user fetched successfully');
    }

    public function update(UpdateTeamUserRequest $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.teams.edit')) {
            return $this->forbiddenResponse();
        }
        $teamUser = User::query()->teamUsers()->where('id', $user->id)->first();

        if (!($teamUser instanceof User)) {
            return $this->notFoundResponse('Team user not found');
        }
        $oldValues = $teamUser->only(['email', 'phone', 'department', 'job_title', 'status']);
        $updated = $this->service->update($teamUser, $request->validated());

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $updated->id,
            'update',
            $oldValues,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.teams.update',
            'api_v1_admin',
            ['user_id' => $updated->id],
            $request->ip()
        );

        return $this->successResponse(TeamUserResource::make($updated), 'Team user updated successfully');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.teams.delete')) {
            return $this->forbiddenResponse();
        }
        $teamUser = User::query()->teamUsers()->where('id', $user->id)->first();

        if (!($teamUser instanceof User)) {
            return $this->notFoundResponse('Team user not found');
        }
        $oldValues = $teamUser->only(['email', 'phone', 'department', 'job_title', 'status']);
        $this->service->delete($teamUser);

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $teamUser->id,
            'delete',
            $oldValues,
            null,
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.teams.delete',
            'api_v1_admin',
            ['user_id' => $teamUser->id],
            $request->ip()
        );

        return $this->successResponse(null, 'Team user deleted successfully');
    }
}
