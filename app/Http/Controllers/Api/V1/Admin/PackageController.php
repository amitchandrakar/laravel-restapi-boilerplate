<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\ListPackagesRequest;
use App\Http\Requests\Api\V1\StorePackageRequest;
use App\Http\Requests\Api\V1\UpdatePackageRequest;
use App\Http\Resources\Api\V1\PackageResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\Package;
use App\Services\PackagePermissionService;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin CRUD for subscription packages and package-feature permissions.
 */
class PackageController extends Controller
{
    public function __construct(
        private readonly PackageService $packageService,
        private readonly PackagePermissionService $packagePermissionService
    ) {}

    /**
     * Paginated package catalog with optional filters.
     */
    public function index(ListPackagesRequest $request): JsonResponse
    {
        try {
            if (!$request->user()?->can('admin.packages.view')) {
                return $this->forbiddenResponse();
            }

            $paginator = $this->packageService->list($request->validated());

            LogUserActivityJob::dispatch(
                (int) $request->user()->id,
                'admin.packages.index',
                'api_v1_admin',
                ['filters' => $request->validated()],
                $request->ip()
            );

            return $this->paginatedResponse(PackageResource::collection($paginator), 'Packages fetched successfully');
        } catch (Throwable $e) {
            Log::error('PackageController@index failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Show a single package with feature permissions.
     */
    public function show(Request $request, Package $package): JsonResponse
    {
        try {
            if (!$request->user()?->can('admin.packages.view')) {
                return $this->forbiddenResponse();
            }

            $package->load('permissions');

            LogUserActivityJob::dispatch(
                (int) $request->user()->id,
                'admin.packages.show',
                'api_v1_admin',
                ['package_id' => $package->id],
                $request->ip()
            );

            return $this->successResponse(PackageResource::make($package), 'Package fetched successfully');
        } catch (Throwable $e) {
            Log::error('PackageController@show failed', [
                'package_id' => $package->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Package-feature permission options for create/edit forms.
     */
    public function permissionOptions(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.packages.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            $this->packagePermissionService->candidatePermissionOptions(),
            'Package permission options fetched successfully'
        );
    }

    /**
     * Create a package and optional feature permission mapping.
     */
    public function store(StorePackageRequest $request): JsonResponse
    {
        try {
            if (!$request->user()?->can('admin.packages.add')) {
                return $this->forbiddenResponse();
            }

            $package = $this->packageService->createPackage($request->validated(), $request->user());

            LogAuditJob::dispatch(
                (int) $request->user()->id,
                'packages',
                (int) $package->id,
                'create',
                null,
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                (int) $request->user()->id,
                'admin.packages.create',
                'api_v1_admin',
                ['package_id' => $package->id],
                $request->ip()
            );

            return $this->createdResponse(PackageResource::make($package), 'Package created successfully');
        } catch (Throwable $e) {
            Log::error('PackageController@store failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a package; permission changes queue candidate sync.
     */
    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        try {
            if (!$request->user()?->can('admin.packages.edit')) {
                return $this->forbiddenResponse();
            }

            $oldValues = $package->only([
                'name',
                'code',
                'duration_unit',
                'monthly_price',
                'yearly_price',
                'is_active',
            ]);
            $updated = $this->packageService->updatePackage($package, $request->validated(), $request->user());

            LogAuditJob::dispatch(
                (int) $request->user()->id,
                'packages',
                (int) $updated->id,
                'update',
                $oldValues,
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                (int) $request->user()->id,
                'admin.packages.update',
                'api_v1_admin',
                ['package_id' => $updated->id],
                $request->ip()
            );

            return $this->successResponse(PackageResource::make($updated), 'Package updated successfully');
        } catch (Throwable $e) {
            Log::error('PackageController@update failed', [
                'package_id' => $package->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Soft-delete a package.
     */
    public function destroy(Request $request, int $package): JsonResponse
    {
        try {
            if (!$request->user()?->can('admin.packages.delete')) {
                return $this->forbiddenResponse();
            }

            $packageModel = Package::query()->find($package);

            if (!($packageModel instanceof Package)) {
                return $this->notFoundResponse('Package not found');
            }

            $oldValues = $packageModel->only([
                'name',
                'code',
                'duration_unit',
                'monthly_price',
                'yearly_price',
                'is_active',
            ]);
            $this->packageService->deletePackage($packageModel, $request->user());

            LogAuditJob::dispatch(
                (int) $request->user()->id,
                'packages',
                (int) $packageModel->id,
                'delete',
                $oldValues,
                null,
                $request->ip(),
                $request->userAgent()
            );
            LogUserActivityJob::dispatch(
                (int) $request->user()->id,
                'admin.packages.delete',
                'api_v1_admin',
                ['package_id' => $packageModel->id],
                $request->ip()
            );

            return $this->successResponse(null, 'Package deleted successfully');
        } catch (Throwable $e) {
            Log::error('PackageController@destroy failed', [
                'package_id' => $package,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
