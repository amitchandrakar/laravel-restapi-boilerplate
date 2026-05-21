<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\ListTeamUsersRequest;
use App\Http\Requests\Api\V1\Admin\StoreTeamUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTeamUserRequest;
use App\Http\Resources\Api\V1\TeamUserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\TeamUserPermissionService;
use App\Services\TeamUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin API for team member (staff) management.
 *
 * Team members are {@see User} records with assignable roles `admin` or `reviewer`,
 * optional direct permissions, and profile/location fields for the admin panel.
 */
class TeamUserController extends Controller
{
    public function __construct(
        private readonly TeamUserService $service,
        private readonly TeamUserPermissionService $permissionService
    ) {}

    /**
     * List team members with pagination and filters.
     */
    public function index(ListTeamUsersRequest $request): JsonResponse
    {
        try {
            $this->authorize('viewAnyTeamMember');

            $paginator = $this->service->list($request->validated());

            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.teams.index',
                'api_v1_admin',
                ['filters' => $request->validated()],
                $request->ip()
            );

            return $this->paginatedResponse(
                TeamUserResource::collection($paginator),
                'Team users fetched successfully'
            );
        } catch (Throwable $e) {
            Log::error('TeamUserController@index failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Permission checkbox options for the team member form.
     */
    public function permissionOptions(Request $request): JsonResponse
    {
        if (!Gate::forUser($request->user())->allows('viewAnyTeamMember')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            $this->permissionService->assignableOptions(),
            'Team permission options fetched successfully'
        );
    }

    /**
     * Create a team member.
     */
    public function store(StoreTeamUserRequest $request): JsonResponse
    {
        try {
            $this->authorize('createTeamMember');

            $validated = $request->validated();
            $user = $this->service->create($validated, $request->file('profile_photo'));

            LogAuditJob::dispatch(
                $this->authenticatedUserId($request),
                'users',
                (int) $user->id,
                'create',
                null,
                $this->auditPayload($validated),
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.teams.create',
                'api_v1_admin',
                ['user_id' => $user->id],
                $request->ip()
            );

            return $this->createdResponse(TeamUserResource::make($user), 'Team user created successfully');
        } catch (Throwable $e) {
            Log::error('TeamUserController@store failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Show a single team member.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        try {
            $teamUser = $this->resolveTeamUser($user);

            if ($teamUser === null) {
                return $this->notFoundResponse('Team user not found');
            }

            $this->authorize('viewTeamMember', $teamUser);

            $teamUser->load(['primaryRole', 'permissions.module']);

            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.teams.show',
                'api_v1_admin',
                ['user_id' => $teamUser->id],
                $request->ip()
            );

            return $this->successResponse(TeamUserResource::make($teamUser), 'Team user fetched successfully');
        } catch (Throwable $e) {
            Log::error('TeamUserController@show failed', [
                'user_uuid' => $user->uuid,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a team member.
     */
    public function update(UpdateTeamUserRequest $request, User $user): JsonResponse
    {
        try {
            $teamUser = $this->resolveTeamUser($user);

            if ($teamUser === null) {
                return $this->notFoundResponse('Team user not found');
            }

            $this->authorize('updateTeamMember', $teamUser);

            $validated = $request->validated();
            $oldValues = $this->service->auditSnapshot($teamUser);
            $updated = $this->service->update($teamUser, $validated, $request->file('profile_photo'));

            LogAuditJob::dispatch(
                $this->authenticatedUserId($request),
                'users',
                (int) $updated->id,
                'update',
                $oldValues,
                $this->auditPayload($validated),
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.teams.update',
                'api_v1_admin',
                ['user_id' => $updated->id],
                $request->ip()
            );

            return $this->successResponse(TeamUserResource::make($updated), 'Team user updated successfully');
        } catch (Throwable $e) {
            Log::error('TeamUserController@update failed', [
                'user_uuid' => $user->uuid,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Soft-delete a team member.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            $teamUser = $this->resolveTeamUser($user);

            if ($teamUser === null) {
                return $this->notFoundResponse('Team user not found');
            }

            $this->authorize('deleteTeamMember', $teamUser);

            $oldValues = $this->service->auditSnapshot($teamUser);
            $this->service->delete($teamUser);

            LogAuditJob::dispatch(
                $this->authenticatedUserId($request),
                'users',
                (int) $teamUser->id,
                'delete',
                $oldValues,
                null,
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.teams.delete',
                'api_v1_admin',
                ['user_id' => $teamUser->id],
                $request->ip()
            );

            return $this->successResponse(null, 'Team user deleted successfully');
        } catch (Throwable $e) {
            Log::error('TeamUserController@destroy failed', [
                'user_uuid' => $user->uuid,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveTeamUser(User $user): ?User
    {
        return User::query()->teamUsers()->where('id', $user->id)->first();
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @return array<string, mixed>
     */
    private function auditPayload(array $validated): array
    {
        $payload = $validated;
        unset($payload['password'], $payload['password_confirmation'], $payload['profile_photo']);

        return $payload;
    }
}
