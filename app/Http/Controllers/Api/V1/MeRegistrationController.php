<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Me\RegistrationCheckoutRequest;
use App\Http\Requests\Api\V1\Payment\ConfirmRegistrationPaymentRequest;
use App\Models\Package;
use App\Services\AuthService;
use App\Services\Payment\RegistrationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MeRegistrationController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RegistrationPaymentService $registrationPaymentService
    ) {}

    public function checkout(RegistrationCheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        if (!$user->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        /** @var Package $package */
        $package = Package::query()
            ->where('uuid', (string) $request->validated('package_uuid'))
            ->where('is_active', true)
            ->firstOrFail();

        $payload = $this->authService->prepareRegistrationCheckout($user, $package);

        return $this->successResponse($payload, 'Registration checkout prepared');
    }

    public function verify(ConfirmRegistrationPaymentRequest $request): JsonResponse
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

        return $this->successResponse(
            [
                'payment_status' => $payment->payment_status,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'Payment verified successfully'
        );
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $packageUuid = $request->query('package_uuid');
        $packageUuidStr = is_string($packageUuid) ? $packageUuid : null;

        $payload = $this->authService->registrationStatusForMember($user, $packageUuidStr);

        return $this->successResponse($payload, 'Registration status fetched');
    }
}
