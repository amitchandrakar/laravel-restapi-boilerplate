<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\RespondContactRequestRequest;
use App\Http\Requests\Api\V1\Candidate\StoreContactRequestRequest;
use App\Models\ContactRequest;
use App\Models\User;
use App\Services\ContactRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CandidateContactRequestController extends Controller
{
    public function __construct(private readonly ContactRequestService $contactRequestService) {}

    public function store(StoreContactRequestRequest $request): JsonResponse
    {
        $from = $request->user();
        if ($from === null || !$from->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can send contact requests.');
        }

        // Permission check intentionally disabled for now (token + role only).
        // if (!$from->can('candidate.send_contact_requests')) {
        //     return $this->forbiddenResponse('You do not have permission to send contact requests.');
        // }

        $to = User::query()->where('uuid', $request->validated('candidateUuid'))->first();
        if ($to === null) {
            throw ValidationException::withMessages([
                'candidateUuid' => ['Candidate not found.'],
            ]);
        }

        try {
            $row = $this->contactRequestService->create(
                $from,
                $to,
                $request->validated('requestMessage')
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->createdResponse(
            [
                'uuid' => $row->uuid,
                'candidateUuid' => $to->uuid,
                'requestStatus' => $row->request_status,
                'requestMessage' => $row->request_message,
                'createdAt' => $row->created_at?->toIso8601String(),
            ],
            'Contact request sent successfully'
        );
    }

    public function respond(RespondContactRequestRequest $request, ContactRequest $contactRequest): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasRole('candidate')) {
            return $this->forbiddenResponse('Only candidates can respond to contact requests.');
        }

        if ((int) $contactRequest->to_user_id !== (int) $user->id) {
            return $this->forbiddenResponse();
        }

        try {
            $updated = $this->contactRequestService->respond(
                $contactRequest,
                (string) $request->validated('decision'),
                $request->validated('responseMessage')
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        return $this->successResponse(
            [
                'uuid' => $updated->uuid,
                'requestStatus' => $updated->request_status,
                'responseMessage' => $updated->response_message,
                'respondedAt' => $updated->responded_at?->toIso8601String(),
            ],
            $updated->request_status === 'accepted'
                ? 'Contact request accepted'
                : 'Contact request rejected'
        );
    }
}
