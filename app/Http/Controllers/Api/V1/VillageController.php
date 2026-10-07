<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Candidate\FindOrCreateVillageRequest;
use App\Services\VillageService;
use App\Support\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;

class VillageController extends Controller
{
    public function __construct(private readonly VillageService $villages) {}

    public function findOrCreate(FindOrCreateVillageRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user?->hasRole('candidate')) {
            return ApiResponseBuilder::error(
                'Only candidates can add villages.',
                403,
                ApiResponseBuilder::ERROR_FORBIDDEN,
                'Forbidden',
                null
            );
        }

        $data = $this->villages->findOrCreate(
            (int) $request->validated('city_id'),
            (string) $request->validated('name')
        );

        return ApiResponseBuilder::success($data, 'Village ready', 200);
    }
}
