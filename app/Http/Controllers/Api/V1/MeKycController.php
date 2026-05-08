<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Me\KycMultipartUploadRequest;
use App\Http\Requests\Api\V1\Me\KycSubmitRequest;
use App\Http\Resources\Api\V1\KycDocumentResource;
use App\Services\KycDocumentService;
use App\Services\KycIdVerificationUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MeKycController extends Controller
{
    private const SESSION_TTL_SECONDS = 3600;

    public function __construct(
        private readonly KycDocumentService $kycDocumentService,
        private readonly KycIdVerificationUploadService $kycIdVerificationUpload
    ) {}

    /**
     * Same data as GET /auth/candidate/kyc/documents — for the native ID verification screen.
     */
    public function documents(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if (!$user->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $docs = $user->verificationDocuments()->orderBy('document_type')->get();

        return $this->successResponse(
            KycDocumentResource::collection($docs)->resolve(),
            'KYC documents fetched successfully'
        );
    }

    public function uploadSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if (!$user->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $sessionId = (string) Str::uuid();
        Cache::put(
            $this->sessionCacheKey((int) $user->id, $sessionId),
            [
                'aadhaar_front_key' => null,
                'aadhaar_back_key' => null,
                'selfie_key' => null,
            ],
            now()->addSeconds(self::SESSION_TTL_SECONDS)
        );

        return $this->successResponse(
            [
                'session_id' => $sessionId,
                'expires_in_seconds' => self::SESSION_TTL_SECONDS,
                'upload_required_fields' => ['aadhaar_front', 'aadhaar_back', 'selfie'],
            ],
            'Upload session created'
        );
    }

    public function upload(KycMultipartUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if (!$user->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $sessionId = (string) $request->validated('session_id');
        $key = $this->sessionCacheKey((int) $user->id, $sessionId);
        /** @var array<string, mixed>|null $payload */
        $payload = Cache::get($key);
        if (!is_array($payload)) {
            return $this->errorResponse('Invalid or expired session_id', 422);
        }

        $userId = (int) $user->id;

        $front = $this->kycIdVerificationUpload->storeWebp($request->file('aadhaar_front'), $userId);
        $back = $this->kycIdVerificationUpload->storeWebp($request->file('aadhaar_back'), $userId);
        $selfie = $this->kycIdVerificationUpload->storeWebp($request->file('selfie'), $userId);

        $merged = array_merge($payload, [
            'aadhaar_front_key' => $front['storage_key'],
            'aadhaar_back_key' => $back['storage_key'],
            'selfie_key' => $selfie['storage_key'],
        ]);

        Cache::put($key, $merged, now()->addSeconds(self::SESSION_TTL_SECONDS));

        return $this->successResponse(
            [
                'session_id' => $sessionId,
                'aadhaar_front_url' => $front['public_url'],
                'aadhaar_back_url' => $back['public_url'],
                'selfie_url' => $selfie['public_url'],
            ],
            'KYC images uploaded'
        );
    }

    public function submit(KycSubmitRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if (!$user->hasRole('candidate')) {
            return $this->forbiddenResponse();
        }

        $sessionId = (string) $request->validated('session_id');
        $key = $this->sessionCacheKey((int) $user->id, $sessionId);
        /** @var array<string, mixed>|null $payload */
        $payload = Cache::get($key);
        if (!is_array($payload)) {
            return $this->errorResponse('Invalid or expired session_id', 422);
        }

        $frontKey = $payload['aadhaar_front_key'] ?? null;
        $aadhaarBackKey = $payload['aadhaar_back_key'] ?? null;
        $selfieKey = $payload['selfie_key'] ?? null;

        if (!is_string($frontKey) || $frontKey === '' || !is_string($aadhaarBackKey) || $aadhaarBackKey === '') {
            return $this->errorResponse('Upload all required documents before submit', 422);
        }

        $doc = $this->kycDocumentService->upsertForCandidate($user, [
            'document_type' => KycDocumentService::DOCUMENT_AADHAAR,
            'document_number_masked' => $request->validated('document_number_masked'),
            'document_front_url' => $frontKey,
            'document_back_url' => $aadhaarBackKey,
            'selfie_url' => is_string($selfieKey) ? $selfieKey : null,
        ]);

        Cache::forget($key);

        return $this->successResponse(KycDocumentResource::make($doc)->resolve(), 'KYC submitted successfully');
    }

    private function sessionCacheKey(int $userId, string $sessionId): string
    {
        return "kyc_multipart:{$userId}:{$sessionId}";
    }
}
