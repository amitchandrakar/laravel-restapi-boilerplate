<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

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
use App\Http\Requests\Api\V1\Candidate\SaveCandidatePreferencesRequest;
use App\Http\Requests\Api\V1\Candidate\UploadCandidateProfileImageRequest;
use App\Http\Resources\Api\V1\AdminCandidateProfileDetailsResource;
use App\Http\Resources\Api\V1\CandidateUserResource;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Models\User;
use App\Services\CandidateProfileSectionService;
use App\Services\ProfileViewService;
use App\Services\UserImageManageService;
use App\Services\UserImageUploadService;
use App\Support\CandidateEntitlements;
use App\Support\UserProfilePhotos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateProfileController extends Controller
{
    public function __construct(
        private readonly CandidateProfileSectionService $service,
        private readonly UserImageUploadService $userImageUploads,
        private readonly UserImageManageService $userImageManage,
        private readonly ProfileViewService $profileViewService
    ) {}

    /**
     * Full profile read model for the authenticated candidate (own profile only).
     */
    public function profileDetails(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(
            AdminCandidateProfileDetailsResource::make($user),
            'Candidate profile details fetched successfully'
        );
    }

    /**
     * Read another candidate's profile (discovery / contact flows).
     */
    public function peerProfileDetails(Request $request, User $candidate): JsonResponse
    {
        $actor = $request->user();

        if (!$actor?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        if (!$candidate->hasRole('candidate')) {
            return $this->notFoundResponse('Candidate not found');
        }

        if (
            (int) $actor->id !== (int) $candidate->id &&
            !CandidateEntitlements::canBrowse($actor) &&
            !$actor->can(CandidateEntitlements::VIEW_FULL_PROFILE)
        ) {
            return $this->forbiddenResponse('You do not have permission to view this profile.');
        }

        $this->profileViewService->recordCandidatePeerView($actor, $candidate);

        return $this->successResponse(
            AdminCandidateProfileDetailsResource::make($candidate),
            'Candidate profile details fetched successfully'
        );
    }

    /**
     * Save multiple profile sections in one request (own profile only).
     */
    public function saveFullProfile(SaveAdminCandidateFullProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $updated = $this->service->saveAllSections($user, $request->validated());
        LogAuditJob::dispatch(
            (int) $user->id,
            'users',
            (int) $user->id,
            'candidate.full_profile.save',
            null,
            ['sections' => array_keys($request->validated())],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $user->id,
            'candidate.full_profile.save',
            'api_v1_candidate',
            ['user_id' => $updated->id],
            $request->ip()
        );

        return $this->successResponse(
            CandidateUserResource::make($updated),
            'Candidate full profile saved successfully'
        );
    }

    /**
     * Notification / privacy preferences for the authenticated candidate.
     */
    public function savePreferences(SaveCandidatePreferencesRequest $request): JsonResponse
    {
        $candidate = $request->user();

        if (!$candidate?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $payload = $request->validated();
        $updates = [];

        if (array_key_exists('phoneAlertsEnabled', $payload)) {
            $updates['phone_alerts_enabled'] = (bool) $payload['phoneAlertsEnabled'];
        }

        if (array_key_exists('emailNotificationsEnabled', $payload)) {
            $updates['email_notifications_enabled'] = (bool) $payload['emailNotificationsEnabled'];
        }

        if (array_key_exists('showOnlineStatus', $payload)) {
            $updates['show_online_status'] = (bool) $payload['showOnlineStatus'];
        }

        if (array_key_exists('hidePhoneNumber', $payload)) {
            $updates['hide_phone_number'] = (bool) $payload['hidePhoneNumber'];
        }

        if ($updates !== []) {
            $candidate->forceFill($updates)->save();
            $candidate->refresh();
        }

        return $this->successResponse(
            [
                'preferences' => [
                    'phoneAlertsEnabled' => (bool) ($candidate->phone_alerts_enabled ?? false),
                    'emailNotificationsEnabled' => (bool) ($candidate->email_notifications_enabled ?? true),
                    'showOnlineStatus' => (bool) ($candidate->show_online_status ?? false),
                    'hidePhoneNumber' => (bool) ($candidate->hide_phone_number ?? true),
                ],
            ],
            'Preferences updated'
        );
    }

    public function saveBasics(SaveCandidateBasicsRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_BASICS);
    }

    public function savePhotos(SaveCandidatePhotosRequest $request): JsonResponse
    {
        return $this->saveSection($request, CandidateProfileSectionService::SECTION_PHOTOS);
    }

    public function listPhotos(Request $request, User $candidate): JsonResponse
    {
        if (
            $deny = $this->guardOwnCandidateProfile($request, $candidate, 'You can only view your own profile photos.')
        ) {
            return $deny;
        }

        return $this->successResponse(
            UserProfilePhotos::listForUser($candidate),
            'Candidate profile photos fetched successfully'
        );
    }

    public function setProfilePhoto(Request $request, User $candidate, string $imageUuid): JsonResponse
    {
        if ($deny = $this->guardOwnCandidateProfile($request, $candidate)) {
            return $deny;
        }

        $data = $this->userImageManage->setProfilePhoto($candidate, $imageUuid);

        if ($data === null) {
            return $this->notFoundResponse('Photo not found');
        }

        $user = $request->user();
        LogAuditJob::dispatch(
            (int) $user->id,
            'users',
            (int) $user->id,
            'candidate.profile.image.set_profile',
            null,
            ['image_id' => $data['id'] ?? null, 'image_uuid' => $data['uuid'] ?? null],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $user->id,
            'candidate.profile.image.set_profile',
            'api_v1_candidate',
            ['image_id' => $data['id'] ?? null, 'image_uuid' => $data['uuid'] ?? null],
            $request->ip()
        );

        return $this->successResponse($data, 'Profile photo updated successfully');
    }

    public function deletePhoto(Request $request, User $candidate, string $imageUuid): JsonResponse
    {
        if ($deny = $this->guardOwnCandidateProfile($request, $candidate)) {
            return $deny;
        }

        $data = $this->userImageManage->softDeletePhoto($candidate, $imageUuid);

        if ($data === null) {
            return $this->notFoundResponse('Photo not found');
        }

        $user = $request->user();
        LogAuditJob::dispatch(
            (int) $user->id,
            'users',
            (int) $user->id,
            'candidate.profile.image.soft_delete',
            null,
            ['image_id' => $data['id'], 'image_uuid' => $data['uuid']],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $user->id,
            'candidate.profile.image.soft_delete',
            'api_v1_candidate',
            ['image_id' => $data['id'], 'image_uuid' => $data['uuid']],
            $request->ip()
        );

        return $this->successResponse($data, 'Photo removed successfully');
    }

    public function uploadPhoto(UploadCandidateProfileImageRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $data = $this->userImageUploads->upload($user, $request->file('image'));

        LogAuditJob::dispatch(
            (int) $user->id,
            'users',
            (int) $user->id,
            'candidate.profile.image.upload',
            null,
            ['image_id' => $data['id'] ?? null],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            (int) $user->id,
            'candidate.profile.image.upload',
            'api_v1_candidate',
            ['image_id' => $data['id'] ?? null],
            $request->ip()
        );

        return $this->successResponse($data, 'Profile image uploaded successfully');
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
            $user->id,
            'candidate.section.save',
            null,
            ['section' => $section],
            $request->ip(),
            $request->userAgent()
        );
        LogUserActivityJob::dispatch(
            $user->id,
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

    private function guardOwnCandidateProfile(
        Request $request,
        User $candidate,
        string $selfOnlyMessage = 'You can only manage your own profile photos.'
    ): ?JsonResponse {
        $auth = $request->user();

        if (!$auth?->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        if (!$candidate->hasRole('candidate')) {
            return $this->notFoundResponse('Candidate not found');
        }

        if ((string) $auth->uuid !== (string) $candidate->uuid) {
            return $this->forbiddenResponse($selfOnlyMessage);
        }

        return null;
    }
}
