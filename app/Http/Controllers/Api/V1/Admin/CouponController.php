<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCouponRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCouponRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.coupons.view')) {
            return $this->forbiddenResponse();
        }

        try {
            $coupons = $this->couponService
                ->listForAdmin()
                ->map(fn(Coupon $coupon): array => $this->couponService->toAdminArray($coupon))
                ->values()
                ->all();

            LogUserActivityJob::dispatch(
                $request->user()->id,
                'admin.coupons.index',
                'api_v1_admin',
                null,
                $request->ip()
            );

            return $this->successResponse($coupons, 'Coupons fetched successfully');
        } catch (Throwable $e) {
            Log::error('CouponController@index failed', ['message' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch coupons', 500);
        }
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.coupons.add')) {
            return $this->forbiddenResponse();
        }

        try {
            $coupon = $this->couponService->create($request->validated());

            LogAuditJob::dispatch(
                $request->user()->id,
                'coupons',
                $coupon->id,
                'create',
                null,
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return $this->createdResponse($this->couponService->toAdminArray($coupon), 'Coupon created successfully');
        } catch (Throwable $e) {
            Log::error('CouponController@store failed', ['message' => $e->getMessage()]);

            return $this->errorResponse('Failed to create coupon', 500);
        }
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        if (!$request->user()?->can('admin.coupons.edit')) {
            return $this->forbiddenResponse();
        }

        try {
            $updated = $this->couponService->update($coupon, $request->validated());

            LogAuditJob::dispatch(
                $request->user()->id,
                'coupons',
                $coupon->id,
                'update',
                null,
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return $this->successResponse($this->couponService->toAdminArray($updated), 'Coupon updated successfully');
        } catch (Throwable $e) {
            Log::error('CouponController@update failed', ['message' => $e->getMessage()]);

            return $this->errorResponse('Failed to update coupon', 500);
        }
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        if (!$request->user()?->can('admin.coupons.delete')) {
            return $this->forbiddenResponse();
        }

        try {
            $this->couponService->delete($coupon);

            LogAuditJob::dispatch(
                $request->user()->id,
                'coupons',
                $coupon->id,
                'delete',
                null,
                null,
                $request->ip(),
                $request->userAgent()
            );

            return $this->successResponse(null, 'Coupon deleted successfully');
        } catch (Throwable $e) {
            Log::error('CouponController@destroy failed', ['message' => $e->getMessage()]);

            return $this->errorResponse('Failed to delete coupon', 500);
        }
    }
}
