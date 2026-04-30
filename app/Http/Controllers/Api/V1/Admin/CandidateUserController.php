<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Candidate\SaveAdminCandidateFullProfileRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateBasicsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateCareerEducationRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateFamilyBackgroundRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateHoroscopeRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateLifestyleRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateLocationFamilyRootsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePartnerPreferencesRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePersonalDetailsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePhotosRequest;
use App\Http\Requests\Api\V1\StoreCandidateUserRequest;
use App\Http\Requests\Api\V1\UpdateCandidateUserRequest;
use App\Http\Resources\Api\V1\CandidateUserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\CandidateProfileSectionService;
use App\Services\CandidateUserService;
use Illuminate\Foundation\Http\FormRequest;
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

    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.view')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
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

    public function update(UpdateCandidateUserRequest $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
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

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.candidates.delete')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
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

    public function saveBasics(
        User $user,
        SaveCandidateBasicsRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_BASICS, $sectionService);
    }

    public function savePhotos(
        User $user,
        SaveCandidatePhotosRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_PHOTOS, $sectionService);
    }

    public function savePersonalDetails(
        User $user,
        SaveCandidatePersonalDetailsRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection(
            $request,
            $user,
            CandidateProfileSectionService::SECTION_PERSONAL_DETAILS,
            $sectionService
        );
    }

    public function saveHoroscope(
        User $user,
        SaveCandidateHoroscopeRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_HOROSCOPE, $sectionService);
    }

    public function saveLocationFamilyRoots(
        User $user,
        SaveCandidateLocationFamilyRootsRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection(
            $request,
            $user,
            CandidateProfileSectionService::SECTION_LOCATION_FAMILY_ROOTS,
            $sectionService
        );
    }

    public function saveCareerEducation(
        User $user,
        SaveCandidateCareerEducationRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection(
            $request,
            $user,
            CandidateProfileSectionService::SECTION_CAREER_EDUCATION,
            $sectionService
        );
    }

    public function saveFamilyBackground(
        User $user,
        SaveCandidateFamilyBackgroundRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection(
            $request,
            $user,
            CandidateProfileSectionService::SECTION_FAMILY_BACKGROUND,
            $sectionService
        );
    }

    public function saveLifestyle(
        User $user,
        SaveCandidateLifestyleRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_LIFESTYLE, $sectionService);
    }

    public function savePartnerPreferences(
        User $user,
        SaveCandidatePartnerPreferencesRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        return $this->saveSection(
            $request,
            $user,
            CandidateProfileSectionService::SECTION_PARTNER_PREFERENCES,
            $sectionService
        );
    }

    public function sectionProgress(
        Request $request,
        User $user,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.view')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }

        return $this->successResponse(
            $sectionService->progress($candidate),
            'Candidate profile progress fetched successfully'
        );
    }

    public function publishProfile(
        Request $request,
        User $user,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
        if (!$candidate instanceof User) {
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

    public function saveFullProfile(
        SaveAdminCandidateFullProfileRequest $request,
        User $user,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }

        $updated = $sectionService->saveAllSections($candidate, $request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $candidate->id,
            'candidate.full_profile.save',
            null,
            ['sections' => array_keys($request->validated())],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.full_profile.save',
            'api_v1_admin',
            ['candidate_id' => $candidate->id],
            $request->ip()
        );

        return $this->successResponse(
            CandidateUserResource::make($updated),
            'Candidate full profile saved successfully'
        );
    }

    public function saveCompleteProfile(
        SaveAdminCandidateFullProfileRequest $request,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }

        $payload = $request->validated();
        $candidateUuid = (string) ($payload['candidate_uuid'] ?? '');
        $candidate = $candidateUuid !== '' ? User::query()->candidates()->where('uuid', $candidateUuid)->first() : null;

        if (!$candidate instanceof User) {
            $firstName = (string) data_get($payload, 'personal_details.first_name', 'Candidate');
            $lastName = (string) data_get($payload, 'personal_details.last_name', 'User');
            $email = (string) data_get($payload, 'basics.email');
            $password = (string) ($payload['password'] ?? 'Password@123');
            $candidateRoleId = (int) \App\Models\Role::query()->where('name', 'candidate')->value('id');

            /** @var User $candidate */
            $candidate = User::query()->create([
                'first_name' => $firstName !== '' ? $firstName : 'Candidate',
                'last_name' => $lastName !== '' ? $lastName : 'User',
                'email' => $email,
                'password' => $password,
                'status' => 'active',
                'role_id' => $candidateRoleId > 0 ? $candidateRoleId : null,
            ]);
            $candidate->syncRoles(['candidate']);
        }

        $updated = $sectionService->saveAllSections($candidate, $payload);
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $candidate->id,
            'candidate.complete_profile.save',
            null,
            ['sections' => array_keys($payload)],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.complete_profile.save',
            'api_v1_admin',
            ['candidate_id' => $candidate->id],
            $request->ip()
        );

        return $this->successResponse(
            CandidateUserResource::make($updated),
            'Candidate complete profile saved successfully'
        );
    }

    private function saveSection(
        FormRequest $request,
        User $user,
        string $section,
        CandidateProfileSectionService $sectionService
    ): JsonResponse {
        if (!$request->user()?->can('admin.candidates.edit')) {
            return $this->forbiddenResponse();
        }
        $candidate = User::query()->candidates()->where('id', $user->id)->first();
        if (!$candidate instanceof User) {
            return $this->notFoundResponse('Candidate not found');
        }
        $updated = $sectionService->saveSection($candidate, $section, $request->validated());
        LogAuditJob::dispatch(
            (int) $request->user()->id,
            'users',
            (int) $candidate->id,
            'candidate.section.save',
            null,
            ['section' => $section],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $request->user()->id,
            'admin.candidates.section.save',
            'api_v1_admin',
            ['candidate_id' => $candidate->id, 'section' => $section],
            $request->ip()
        );

        return $this->successResponse(
            ['section' => $section, 'completedSections' => $updated->completed_sections_json],
            'Candidate section saved successfully'
        );
    }
}
