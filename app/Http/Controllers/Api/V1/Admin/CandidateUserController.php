<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\StoreCandidateUserRequest;
use App\Http\Requests\Api\V1\UpdateCandidateUserRequest;
use App\Http\Resources\Api\V1\CandidateUserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\CandidateUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateUserController extends Controller
{
    public function __construct(private readonly CandidateUserService $service) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.view')) {
            return $this->forbiddenResponse();
        }
        $paginator = $this->service->list((int) $request->integer('perPage', 15));
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.index',
            'api_v1_admin',
            null,
            $request->ip()
        );

        return $this->paginatedResponse(
            CandidateUserResource::collection($paginator),
            'Candidates fetched successfully'
        );
    }

    public function store(StoreCandidateUserRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.add')) {
            return $this->forbiddenResponse();
        }
        $user = $this->service->create($request->validated());
        $user->syncRoles(['candidate']);
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $user->id,
            'create',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.create',
            'api_v1_admin',
            ['user_id' => $user->id],
            $request->ip()
        );

        return $this->createdResponse(CandidateUserResource::make($user), 'Candidate created successfully');
    }

    public function show(Request $request, int $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.view')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->find($user);
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.show',
            'api_v1_admin',
            ['user_id' => $candidate->id],
            $request->ip()
        );

        return $this->successResponse(CandidateUserResource::make($candidate), 'Candidate fetched successfully');
    }

    public function update(UpdateCandidateUserRequest $request, int $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->find($user);
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }
        $oldValues = $candidate->only(['email', 'phone', 'status', 'current_city']);
        $updated = $this->service->update($candidate, $request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $updated->id,
            'update',
            $oldValues,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.update',
            'api_v1_admin',
            ['user_id' => $updated->id],
            $request->ip()
        );

        return $this->successResponse(CandidateUserResource::make($updated), 'Candidate updated successfully');
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.delete')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->find($user);
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }
        $oldValues = $candidate->only(['email', 'phone', 'status', 'current_city']);
        $this->service->delete($candidate);
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $candidate->id,
            'delete',
            $oldValues,
            null,
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.delete',
            'api_v1_admin',
            ['user_id' => $candidate->id],
            $request->ip()
        );

        return $this->successResponse(null, 'Candidate deleted successfully');
    }
}
