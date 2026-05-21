<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\AdminImpersonateCandidateRequest;
use App\Http\Requests\Api\V1\Admin\ExportCandidatesRequest;
use App\Http\Requests\Api\V1\Admin\ImportCandidatesRequest;
use App\Http\Requests\Api\V1\Admin\ListCandidatesRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCandidateFeaturedRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCandidateProfileStatusRequest;
use App\Http\Requests\Api\V1\StoreCandidateUserRequest;
use App\Http\Requests\Api\V1\UpdateCandidateUserRequest;
use App\Http\Resources\Api\V1\AuthLoginResource;
use App\Http\Resources\Api\V1\CandidateUserResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Jobs\StartUserSessionJob;
use App\Models\User;
use App\Services\CandidateCsvExportService;
use App\Services\CandidateCsvImportService;
use App\Services\CandidateImpersonationService;
use App\Services\CandidateProfileSectionService;
use App\Services\CandidateUserService;
use App\Services\FeaturedCandidateService;
use App\Support\SanctumPlainTokenHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Admin CRUD and operational actions for candidate (member) users.
 */
class CandidateUserController extends Controller
{
    public function __construct(
        private readonly CandidateUserService $service,
        private readonly FeaturedCandidateService $featuredCandidateService,
        private readonly CandidateImpersonationService $impersonationService,
        private readonly CandidateCsvExportService $csvExportService,
        private readonly CandidateCsvImportService $csvImportService
    ) {}

    /**
     * List candidates with pagination and filters (bucket, search, profile_status, etc.).
     */
    public function index(ListCandidatesRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->list($request->validated());
        } catch (Throwable $e) {
            Log::error('admin.candidates.index_failed', [
                'actor_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.index',
            'api_v1_admin',
            ['filters' => $request->validated()],
            $request->ip()
        );

        return $this->paginatedResponse(
            CandidateUserResource::collection($paginator),
            'Candidates fetched successfully'
        );
    }

    /**
     * Stream candidates as CSV using the same filters as the index.
     */
    public function export(ExportCandidatesRequest $request): StreamedResponse|JsonResponse
    {
        $filters = $request->validated();
        $actorId = (int) $request->user()->id;

        try {
            $candidates = $this->csvExportService->candidatesForExport($filters);
            $rows = $this->csvExportService->rowsForCsv($candidates);
            $filename = $this->csvExportService->filename($filters);
        } catch (Throwable $e) {
            Log::error('admin.candidates.export_failed', [
                'actor_id' => $actorId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogAuditJob::dispatch(
            $actorId,
            'users',
            0,
            'export',
            null,
            ['filters' => $filters, 'count' => $candidates->count()],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            $actorId,
            'admin.candidates.export',
            'api_v1_admin',
            ['filters' => $filters, 'count' => $candidates->count()],
            $request->ip()
        );

        $headers = CandidateCsvExportService::HEADERS;

        return response()->streamDownload(
            static function () use ($headers, $rows): void {
                $out = fopen('php://output', 'wb');

                if ($out === false) {
                    return;
                }

                fputcsv($out, $headers);

                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
            ]
        );
    }

    /**
     * Import basic candidate rows from CSV (sync for small files, queued otherwise).
     */
    public function import(ImportCandidatesRequest $request): JsonResponse
    {
        $actorId = (int) $request->user()->id;
        $file = $request->file('file');

        if ($file === null) {
            return $this->errorResponse('CSV file is required.', 422);
        }

        try {
            $result = $this->csvImportService->importFromUpload($file, $actorId);
        } catch (Throwable $e) {
            Log::error('admin.candidates.import_failed', [
                'actor_id' => $actorId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogAuditJob::dispatch(
            $actorId,
            'users',
            0,
            'import',
            null,
            [
                'import_id' => $result['import_id'],
                'queued' => $result['queued'],
                'total_rows' => $result['total_rows'],
            ],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            $actorId,
            'admin.candidates.import',
            'api_v1_admin',
            [
                'import_id' => $result['import_id'],
                'queued' => $result['queued'],
                'total_rows' => $result['total_rows'],
            ],
            $request->ip()
        );

        if ($result['queued']) {
            return $this->successResponse($result, 'Candidate import queued', 202);
        }

        return $this->successResponse($result, 'Candidate import completed');
    }

    /**
     * Poll status for a queued candidate CSV import.
     */
    public function importStatus(Request $request, string $importId): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.import')) {
            return $this->forbiddenResponse();
        }

        $status = $this->csvImportService->batchStatus($importId);

        if ($status === null) {
            return $this->notFoundResponse('Import batch not found');
        }

        return $this->successResponse($status, 'Import status fetched successfully');
    }

    /**
     * Issue a short-lived app token to act as the candidate (admin panel opens member app).
     */
    public function impersonate(AdminImpersonateCandidateRequest $request, User $user): JsonResponse
    {
        $admin = $request->user();

        if (!($admin instanceof User)) {
            return $this->unauthorizedResponse();
        }

        if (!$user->hasRole('candidate')) {
            return $this->notFoundResponse('Candidate not found');
        }

        try {
            $result = $this->impersonationService->impersonate($user, $admin);
        } catch (Throwable $e) {
            Log::error('admin.candidates.impersonate_failed', [
                'admin_id' => $admin->id,
                'candidate_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $meta = $this->requestMeta($request);
        $tokenHash = SanctumPlainTokenHasher::hashPlainTextToken((string) $result['token']);
        StartUserSessionJob::dispatchSync($user->id, $tokenHash, null, $meta['ip'], $meta['ua'], $meta['device_id']);

        LogAuditJob::dispatch(
            (int) $admin->id,
            'users',
            (int) $user->id,
            'impersonate',
            null,
            ['candidate_uuid' => $user->uuid],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $admin->id,
            'admin.candidates.impersonate',
            'api_v1_admin',
            ['candidate_id' => $user->id, 'candidate_uuid' => $user->uuid],
            $request->ip()
        );

        return $this->successResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'session_token_hash' => $tokenHash,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ])->resolve(),
            'Impersonation token issued successfully'
        );
    }

    /**
     * Create a candidate with validated basic details. Use section endpoints for full profile data.
     */
    public function store(StoreCandidateUserRequest $request): JsonResponse
    {
        $actorId = (int) $request->user()->id;

        try {
            $user = $this->service->create($request->validated(), $actorId);
            $user->syncRoles(['candidate']);
        } catch (Throwable $e) {
            Log::error('admin.candidates.store_failed', [
                'actor_id' => $actorId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogAuditJob::dispatch(
            $actorId,
            'users',
            (int) $user->id,
            'create',
            null,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            $actorId,
            'admin.candidates.create',
            'api_v1_admin',
            ['user_id' => $user->id],
            $request->ip()
        );

        return $this->createdResponse(CandidateUserResource::make($user), 'Candidate created successfully');
    }

    /**
     * Fetch a single candidate summary card (admin list row shape).
     */
    public function show(Request $request, string $user): JsonResponse
    {
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
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

    public function update(UpdateCandidateUserRequest $request, string $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }
        $oldValues = $candidate->only(['email', 'phone', 'status', 'current_city']);
        $updated = $this->service->update($candidate, $request->validated(), (int) $request->user()->id);
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

    public function destroy(Request $request, string $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.delete')) {
            return $this->forbiddenResponse();
        }
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }
        $oldValues = $candidate->only(['email', 'phone', 'status', 'profile_status', 'current_city']);
        $this->service->delete($candidate, (int) $request->user()->id);
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

    /**
     * Restore a soft-deleted candidate.
     */
    public function restore(Request $request, string $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }

        $candidate = $this->findCandidateByUuid($user, withTrashed: true);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }

        try {
            $restored = $this->service->restore($candidate, (int) $request->user()->id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('admin.candidates.restore_failed', [
                'uuid' => $user,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $restored->id,
            'restore',
            null,
            ['profile_status' => $restored->profile_status],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.restore',
            'api_v1_admin',
            ['user_id' => $restored->id],
            $request->ip()
        );

        return $this->successResponse(CandidateUserResource::make($restored), 'Candidate restored successfully');
    }

    /**
     * Update profile_status (draft, under_review, published, suspended, spam).
     */
    public function updateProfileStatus(UpdateCandidateProfileStatusRequest $request, string $user): JsonResponse
    {
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }

        $oldStatus = (string) ($candidate->profile_status ?? 'draft');
        $newStatus = (string) $request->validated('profile_status');

        try {
            $updated = $this->service->updateProfileStatus($candidate, $newStatus, (int) $request->user()->id);
        } catch (Throwable $e) {
            Log::error('admin.candidates.profile_status_failed', [
                'user_id' => $candidate->id,
                'profile_status' => $newStatus,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $updated->id,
            'profile_status',
            ['profile_status' => $oldStatus],
            [
                'profile_status' => $newStatus,
                'reason' => $request->validated('reason'),
            ],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.profile_status',
            'api_v1_admin',
            ['user_id' => $updated->id, 'profile_status' => $newStatus],
            $request->ip()
        );

        return $this->successResponse(
            CandidateUserResource::make($updated),
            'Candidate profile status updated successfully'
        );
    }

    public function publishProfile(
        Request $request,
        string $user,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }
        $result = $sectionService->publish($candidate);

        if ($result['published'] !== true) {
            return $this->validationErrorResponse(
                collect($result['missingSections'])
                    ->mapWithKeys(static fn(string $section): array => [$section => ['Section is not completed']])
                    ->all(),
                'Candidate profile is incomplete'
            );
        }

        return $this->successResponse($result, 'Candidate profile published successfully');
    }

    public function setFeatured(UpdateCandidateFeaturedRequest $request, string $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.feature')) {
            return $this->forbiddenResponse();
        }
        $candidate = $this->findCandidateByUuid($user);

        if (!($candidate instanceof User)) {
            return $this->notFoundResponse('Candidate not found');
        }

        $isFeatured = (bool) $request->validated('isFeatured');

        try {
            $updated = $this->featuredCandidateService->setFeatured(
                $candidate,
                $isFeatured,
                (int) $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $updated->id,
            'candidate.featured',
            null,
            ['is_featured' => $isFeatured],
            $request->ip(),
            $request->userAgent()
        );

        return $this->successResponse(
            CandidateUserResource::make($updated),
            $isFeatured ? 'Candidate marked as featured' : 'Candidate unmarked as featured'
        );
    }

    private function findCandidateByUuid(string $uuid, bool $withTrashed = false): ?User
    {
        $query = User::query()->candidates()->where('uuid', $uuid);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @return array{ip: string|null, ua: string, device_id: string, device_name: string, os_name: string}
     */
    private function requestMeta(Request $request): array
    {
        $ua = (string) ($request->userAgent() ?? 'unknown');

        return [
            'ip' => $request->ip(),
            'ua' => $ua,
            'device_id' => hash('sha256', $ua . '|' . (string) $request->ip()),
            'device_name' => str($ua)->limit(120, '')->toString(),
            'os_name' => str($ua)->contains('Windows')
                ? 'Windows'
                : (str($ua)->contains('Macintosh')
                    ? 'macOS'
                    : 'Web'),
        ];
    }
}
