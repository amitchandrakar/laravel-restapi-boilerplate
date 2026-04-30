<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Candidate\SaveCandidateBasicsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateCareerEducationRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateFamilyBackgroundRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateHoroscopeRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateLifestyleRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidateLocationFamilyRootsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePartnerPreferencesRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePersonalDetailsRequest;
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePhotosRequest;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Services\CandidateProfileSectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateProfileController extends Controller
{
    public function __construct(private readonly CandidateProfileSectionService $service) {}

    public function saveBasics(SaveCandidateBasicsRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_BASICS);
    }

    public function savePhotos(SaveCandidatePhotosRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_PHOTOS);
    }

    public function savePersonalDetails(SaveCandidatePersonalDetailsRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_PERSONAL_DETAILS);
    }

    public function saveHoroscope(SaveCandidateHoroscopeRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_HOROSCOPE);
    }

    public function saveLocationFamilyRoots(SaveCandidateLocationFamilyRootsRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_LOCATION_FAMILY_ROOTS);
    }

    public function saveCareerEducation(SaveCandidateCareerEducationRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_CAREER_EDUCATION);
    }

    public function saveFamilyBackground(SaveCandidateFamilyBackgroundRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_FAMILY_BACKGROUND);
    }

    public function saveLifestyle(SaveCandidateLifestyleRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_LIFESTYLE);
    }

    public function savePartnerPreferences(SaveCandidatePartnerPreferencesRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_PARTNER_PREFERENCES);
    }

    public function progress(Request $request): JsonResponse
    {
        if (!$request->user()?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            $this->service->progress($request->user()),
            'Candidate profile progress fetched successfully'
        );
    }

    public function publish(Request $request): JsonResponse
    {
        if (!$request->user()?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $result = $this->service->publish($request->user());

        if ($result['published'] !== true) {
            return $this->errorResponse(
                'Candidate profile is incomplete',
                422,
                null,
                null,
                collect($result['missingSections'])
                    ->map(
                        static fn(string $section): array => [
                            'field' => $section,
                            'message' => 'Section is not completed',
                        ]
                    )
                    ->all()
            );
        }

        return $this->successResponse($result, 'Candidate profile published successfully');
    }

    private function saveSection(FormRequest $request, string $section): JsonResponse
    {
        $user = $request->user();
        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }
        $updated = $this->service->saveSection($user, $section, $request->validated());
        LogAuditJob::dispatch(
            (int) $user->id,
            'users',
            (int) $user->id,
            'candidate.section.save',
            null,
            ['section' => $section],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $user->id,
            'candidate.section.save',
            'api_v1_candidate',
            ['section' => $section],
            $request->ip()
        );

        return $this->successResponse(
            ['section' => $section, 'completedSections' => $updated->completed_sections_json],
            'Candidate section saved successfully'
        );
    }
}
