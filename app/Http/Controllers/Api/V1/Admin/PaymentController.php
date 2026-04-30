<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.payments.view')) {
            return $this->forbiddenResponse();
        }

        $perPage = (int) $request->integer('perPage', 15);
        $paginator = $this->paymentService->paginatePayments($perPage);
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.payments.index',
            'api_v1_admin',
            ['per_page' => $perPage],
            $request->ip()
        );

        return $this->paginatedResponse(PaymentResource::collection($paginator), 'Payments fetched successfully');
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        if (!$request->user()?->can('admin.payments.view')) {
            return $this->forbiddenResponse();
        }

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.payments.show',
            'api_v1_admin',
            ['payment_id' => $payment->id],
            $request->ip()
        );

        return $this->successResponse(PaymentResource::make($payment), 'Payment fetched successfully');
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.payments.add')) {
            return $this->forbiddenResponse();
        }

        $payment = $this->paymentService->createPayment($request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'payments',
            (int) $payment->id,
            'create',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.payments.create',
            'api_v1_admin',
            ['payment_id' => $payment->id],
            $request->ip()
        );

        return $this->createdResponse(PaymentResource::make($payment), 'Payment created successfully');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        if (!$request->user()?->can('admin.payments.edit')) {
            return $this->forbiddenResponse();
        }

        $oldValues = $payment->toArray();
        $updated = $this->paymentService->updatePayment($payment, $request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'payments',
            (int) $updated->id,
            'update',
            $oldValues,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.payments.update',
            'api_v1_admin',
            ['payment_id' => $updated->id],
            $request->ip()
        );

        return $this->successResponse(PaymentResource::make($updated), 'Payment updated successfully');
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        if (!$request->user()?->can('admin.payments.delete')) {
            return $this->forbiddenResponse();
        }

        $oldValues = $payment->toArray();
        $this->paymentService->deletePayment($payment);
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'payments',
            (int) $payment->id,
            'delete',
            $oldValues,
            null,
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.payments.delete',
            'api_v1_admin',
            ['payment_id' => $payment->id],
            $request->ip()
        );

        return $this->successResponse(null, 'Payment deleted successfully');
    }
}
