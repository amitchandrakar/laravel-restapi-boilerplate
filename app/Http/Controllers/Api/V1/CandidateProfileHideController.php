<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\ProfileHideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CandidateProfileHideController extends Controller
{
    public function __construct(private readonly ProfileHideService $hideService) {}

    public function store(Request $request, User $candidate): JsonResponse
    {
        $viewer = $request->user();

        if ($viewer === null || !$viewer->hasRole('candidate', 'web')) {
            return $this->forbiddenResponse('Only candidates can hide profiles.');
        }

        try {
            $row = $this->hideService->hide($viewer, $candidate);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->createdResponse(
            [
                'uuid' => $row->uuid,
                'hiddenUserUuid' => $candidate->uuid,
                'createdAt' => $row->created_at->toIso8601String(),
            ],
            'Profile hidden successfully'
        );
    }

    public function destroy(Request $request, User $candidate): JsonResponse
    {
        $viewer = $request->user();

        if ($viewer === null || !$viewer->hasRole('candidate', 'web')) {
            return $this->forbiddenResponse('Only candidates can unhide profiles.');
        }

        $this->hideService->unhideForViewer($viewer, $candidate);

        return $this->successResponse(null, 'Profile unmarked successfully');
    }
}
