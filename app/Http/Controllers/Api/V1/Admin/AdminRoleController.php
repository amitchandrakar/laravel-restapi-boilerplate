<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\StoreAdminRoleRequest;
use App\Http\Requests\Api\V1\UpdateAdminRoleRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\Role;
use App\Services\AdminRoleService;
use App\Support\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function __construct(private readonly AdminRoleService $adminRoleService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.roles.view')) {
            return $this->forbiddenResponse();
        }

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.roles.index',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->successResponse($this->adminRoleService->index(), 'Roles fetched successfully');
    }

    public function store(StoreAdminRoleRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.roles.edit')) {
            return $this->forbiddenResponse();
        }

        $created = $this->adminRoleService->create($request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'roles',
            (int) $created->id,
            'create',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.roles.store',
            'api_v1_admin',
            ['role_id' => $created->id],
            $request->ip()
        );

        return $this->successResponse(
            [
                'role' => [
                    'id' => $created->id,
                    'uuid' => $created->uuid,
                    'name' => $created->name,
                    'title' => $created->title,
                    'description' => $created->description,
                    'guardName' => $created->guard_name,
                    'isSystem' => (bool) $created->is_system,
                    'isDefaultRegistration' => (bool) ($created->is_default_registration ?? false),
                ],
            ],
            'Role created successfully'
        );
    }

    public function permissions(Request $request, Role $role): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.roles.view')) {
            return $this->forbiddenResponse();
        }

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.roles.permissions',
            'api_v1_admin',
            ['role_uuid' => $role->uuid],
            $request->ip()
        );

        return $this->successResponse(
            $this->adminRoleService->permissionsForRole($role),
            'Role permissions fetched successfully'
        );
    }

    public function update(UpdateAdminRoleRequest $request, Role $role): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.roles.edit')) {
            return $this->forbiddenResponse();
        }

        $oldValues = $role->only(['name', 'title', 'description', 'is_default_registration']);
        $updated = $this->adminRoleService->update($role, $request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'roles',
            (int) $updated->id,
            'update',
            $oldValues,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.roles.update',
            'api_v1_admin',
            ['role_id' => $updated->id],
            $request->ip()
        );

        return $this->successResponse(
            [
                'role' => [
                    'id' => $updated->id,
                    'uuid' => $updated->uuid,
                    'name' => $updated->name,
                    'title' => $updated->title,
                    'description' => $updated->description,
                    'guardName' => $updated->guard_name,
                    'isSystem' => (bool) $updated->is_system,
                    'isDefaultRegistration' => (bool) ($updated->is_default_registration ?? false),
                ],
            ],
            'Role updated successfully'
        );
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if (!$request->user()?->can('admin.settings.roles.edit')) {
            return $this->forbiddenResponse();
        }

        if ($role->is_system) {
            return $this->errorResponse(
                'System roles cannot be deleted.',
                422,
                ApiResponseBuilder::ERROR_VALIDATION,
                'System roles cannot be deleted.',
                null
            );
        }

        $oldValues = $role->only(['name', 'title', 'description', 'is_system', 'is_default_registration']);

        try {
            $this->adminRoleService->delete($role);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422,
                ApiResponseBuilder::ERROR_VALIDATION,
                $e->getMessage(),
                null
            );
        }

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'roles',
            (int) $role->id,
            'delete',
            $oldValues,
            null,
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.settings.roles.delete',
            'api_v1_admin',
            ['role_id' => $role->id],
            $request->ip()
        );

        return $this->successResponse(null, 'Role deleted successfully');
    }
}
