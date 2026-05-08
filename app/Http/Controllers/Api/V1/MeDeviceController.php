<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeDeviceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        if ($request->user() === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $request->validate([
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:64'],
            'device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return $this->successResponse(
            [
                'registered' => false,
                'stub' => true,
            ],
            'Push device registration is not persisted yet'
        );
    }
}
