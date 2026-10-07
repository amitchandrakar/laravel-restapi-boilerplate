<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Me\RegistrationCheckoutRequest;
use App\Models\Package;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeRegistrationController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function checkout(RegistrationCheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        if (!$user->hasRole('candidate', 'web')) {
            return $this->forbiddenResponse();
        }

        /** @var Package $package */
        $package = Package::query()
            ->where('uuid', (string) $request->validated('package_uuid'))
            ->where('is_active', true)
            ->firstOrFail();

        $payload = $this->authService->prepareRegistrationCheckout($user, $package, $request->validated('coupon_code'));

        return $this->successResponse($payload, 'Registration checkout prepared');
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
