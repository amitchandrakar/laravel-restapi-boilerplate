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
use App\Http\Resources\Api\V1\AdminCandidateProfileDetailsResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\CandidateProfileSectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCandidateProfileController extends Controller
{
    public function __construct(private readonly CandidateProfileSectionService $service) {}

    public function profileDetails(User $user): JsonResponse
    {
        if ($deny = $this->guardCandidate($user)) {
            return $deny;
        }

        return $this->successResponse(
            AdminCandidateProfileDetailsResource::make($user),
            'Candidate profile details fetched successfully'
        );
    }

    public function sectionProgress(User $user): JsonResponse
    {
        if ($deny = $this->guardCandidate($user)) {
            return $deny;
        }

        return $this->successResponse(
            $this->service->progress($user),
            'Candidate profile progress fetched successfully'
        );
    }

    public function saveFullProfile(SaveAdminCandidateFullProfileRequest $request, User $user): JsonResponse
    {
        if ($deny = $this->guardCandidate($user)) {
            return $deny;
        }

        $updated = $this->service->saveAllSections($user, $request->validated());
        $this->logSectionActivity($request, $user, 'candidate.full_profile.save', array_keys($request->validated()));

        return $this->successResponse(
            AdminCandidateProfileDetailsResource::make($updated),
            'Candidate profile saved successfully'
        );
    }

    public function saveBasics(SaveCandidateBasicsRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_BASICS);
    }

    public function savePhotos(SaveCandidatePhotosRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_PHOTOS);
    }

    public function savePersonalDetails(SaveCandidatePersonalDetailsRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_PERSONAL_DETAILS);
    }

    public function saveHoroscope(SaveCandidateHoroscopeRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_HOROSCOPE);
    }

    public function saveLocationFamilyRoots(SaveCandidateLocationFamilyRootsRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_LOCATION_FAMILY_ROOTS);
    }

    public function saveCareerEducation(SaveCandidateCareerEducationRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_CAREER_EDUCATION);
    }

    public function saveFamilyBackground(SaveCandidateFamilyBackgroundRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_FAMILY_BACKGROUND);
    }

    public function saveLifestyle(SaveCandidateLifestyleRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_LIFESTYLE);
    }

    public function savePartnerPreferences(SaveCandidatePartnerPreferencesRequest $request, User $user): JsonResponse
    {
        return $this->saveSection($request, $user, CandidateProfileSectionService::SECTION_PARTNER_PREFERENCES);
    }

    private function saveSection(FormRequest $request, User $user, string $section): JsonResponse
    {
        if ($deny = $this->guardCandidate($user)) {
            return $deny;
        }

        $updated = $this->service->saveSection($user, $section, $request->validated());
        $this->logSectionActivity($request, $user, 'candidate.section.save', [$section]);

        return $this->successResponse(
            [
                'section' => $section,
                'completedSections' => $updated->completed_sections_json,
            ],
            'Candidate section saved successfully'
        );
    }

    private function guardCandidate(User $user): ?JsonResponse
    {
        if (!$user->hasRole('candidate')) {
            return $this->notFoundResponse('Candidate not found');
        }

        return null;
    }

    /**
     * @param  list<string>  $sections
     */
    private function logSectionActivity(Request $request, User $candidate, string $action, array $sections): void
    {
        $actor = $request->user();

        if (!($actor instanceof User)) {
            return;
        }

        LogAuditJob::dispatch(
            $actor->id,
            'users',
            $candidate->id,
            $action,
            null,
            ['sections' => $sections, 'candidate_uuid' => $candidate->uuid],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            $actor->id,
            $action,
            'api_v1_admin_candidates',
            ['sections' => $sections, 'candidate_uuid' => $candidate->uuid],
            $request->ip()
        );
    }
}
