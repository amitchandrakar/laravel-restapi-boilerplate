<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\UserPushDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeDeviceController extends Controller
{
    public function __construct(private readonly UserPushDeviceService $devices) {}

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $validated = Validator::make($request->all(), [
            'fcm_token' => ['required', 'string', 'max:4096'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:64'],
            'device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:64'],
        ])->validate();

        try {
            $device = $this->devices->register($user, $validated);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->successResponse(
            [
                'registered' => true,
                'stub' => false,
                'uuid' => $device->uuid,
                'platform' => $device->platform,
                'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
            ],
            'Push device registered'
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $validated = Validator::make($request->all(), [
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'all' => ['sometimes', 'boolean'],
        ])->validate();

        if (!empty($validated['all'])) {
            $this->devices->unregisterAll($user);
        } elseif (!empty($validated['fcm_token'])) {
            $this->devices->unregisterToken($user, (string) $validated['fcm_token']);
        } else {
            return $this->validationErrorResponse(
                ['fcm_token' => ['Provide fcm_token or all=true.']],
                'Validation failed'
            );
        }

        return $this->successResponse(['unregistered' => true], 'Push device unregistered');
    }
}
