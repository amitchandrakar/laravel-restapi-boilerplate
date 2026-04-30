<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
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

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageService $packageService,
        private readonly PackagePermissionService $packagePermissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.packages.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = (int) $request->integer('perPage', 15);
        $paginator = $this->packageService->paginatePackages($perPage);
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.packages.index',
            'api_v1_admin',
            [
                'per_page' => $perPage,
            ],
            $request->ip()
        );

        return $this->paginatedResponse(PackageResource::collection($paginator), 'Packages fetched successfully');
    }

    public function show(Request $request, Package $package): JsonResponse
    {
        if (!$request->user()?->can('admin.packages.view')) {
            return $this->forbiddenResponse();
        }
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.packages.show',
            'api_v1_admin',
            [
                'package_id' => $package->id,
            ],
            $request->ip()
        );

        return $this->successResponse(PackageResource::make($package), 'Package fetched successfully');
    }

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

    public function store(StorePackageRequest $request): JsonResponse
    {
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
            [
                'package_id' => $package->id,
            ],
            $request->ip()
        );

        return $this->createdResponse(PackageResource::make($package), 'Package created successfully');
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        if (!$request->user()?->can('admin.packages.edit')) {
            return $this->forbiddenResponse();
        }

        $oldValues = $package->only(['name', 'code', 'duration_unit', 'monthly_price', 'yearly_price', 'is_active']);
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
            [
                'package_id' => $updated->id,
            ],
            $request->ip()
        );

        return $this->successResponse(PackageResource::make($updated), 'Package updated successfully');
    }

    public function destroy(Request $request, int $package): JsonResponse
    {
        if (!$request->user()?->can('admin.packages.delete')) {
            return $this->forbiddenResponse();
        }

        $packageModel = Package::query()->find($package);
        if (!$packageModel instanceof Package) {
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
            [
                'package_id' => $packageModel->id,
            ],
            $request->ip()
        );

        return $this->successResponse(null, 'Package deleted successfully');
    }
}
