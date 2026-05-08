<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Payment\ConfirmRegistrationPaymentRequest;
use App\Models\Subscription;
use App\Services\Payment\RazorpayClient;
use App\Services\Payment\RegistrationPaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class RegistrationPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RegistrationPaymentService $registrationPaymentService,
        private readonly RazorpayClient $razorpayClient
    ) {}

    public function confirm(ConfirmRegistrationPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        try {
            $payment = $this->registrationPaymentService->confirmCheckoutPayment(
                $user,
                (string) $request->input('razorpay_order_id'),
                (string) $request->input('razorpay_payment_id'),
                (string) $request->input('razorpay_signature')
            );
        } catch (ConflictHttpException $e) {
            return $this->errorResponse($e->getMessage(), 409);
        }

        $user->refresh();
        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()->find($payment->subscription_id);
        $endsAt = null;
        if ($subscription !== null && $subscription->ends_at !== null) {
            $endsAt = Carbon::parse($subscription->ends_at)->toIso8601String();
        }

        return $this->successResponse(
            [
                'paymentStatus' => $payment->payment_status,
                'subscription' => [
                    'status' => $subscription !== null ? $subscription->subscription_status : null,
                    'endsAt' => $endsAt,
                ],
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'Payment confirmed successfully'
        );
    }

    public function status(Request $request, string $paymentUuid): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $payload = $this->registrationPaymentService->statusPayload($user, $paymentUuid);

        return $this->successResponse($payload, 'Payment status fetched successfully');
    }

    public function webhook(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        if ($signature === '' || !$this->razorpayClient->verifyWebhookSignature($raw, $signature)) {
            return $this->errorResponse('Invalid webhook signature', 401);
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $this->registrationPaymentService->handleWebhookEvent($decoded);
        } catch (Throwable $e) {
            Log::warning('razorpay_webhook_failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Webhook processing failed', 422);
        }

        return $this->successResponse(['received' => true], 'Webhook processed', 200);
    }
}
