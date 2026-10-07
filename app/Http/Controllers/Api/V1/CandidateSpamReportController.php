<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\ReportProfileSpamRequest;
use App\Models\User;
use App\Services\ProfileSpamReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CandidateSpamReportController extends Controller
{
    public function __construct(private readonly ProfileSpamReportService $spamReportService) {}

    public function store(ReportProfileSpamRequest $request, User $candidate): JsonResponse
    {
        $reporter = $request->user();

        if ($reporter === null || !$reporter->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can report profiles.');
        }

        try {
            $report = $this->spamReportService->report($reporter, $candidate, (string) $request->validated('reason'));
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->createdResponse(
            [
                'uuid' => $report->uuid,
                'reportedUserUuid' => $candidate->uuid,
                'status' => $report->status,
                'reason' => $report->reason,
                'createdAt' => $report->created_at?->toIso8601String(),
            ],
            'Profile reported successfully'
        );
    }
}
