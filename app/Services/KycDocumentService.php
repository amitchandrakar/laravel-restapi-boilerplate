<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserVerificationDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class KycDocumentService
{
    public function __construct(private readonly KycIdVerificationUploadService $kycIdVerificationUpload) {}

    public const DOCUMENT_AADHAAR = 'aadhaar';

    public const DOCUMENT_DRIVING_LICENSE = 'driving_license';

    /** @return list<string> */
    public static function allowedDocumentTypes(): array
    {
        return [self::DOCUMENT_AADHAAR, self::DOCUMENT_DRIVING_LICENSE];
    }

    /**
     * Candidate upsert: create or update when status allows resubmission.
     *
     * @param  array{document_type: string, document_number_masked?: string|null, document_front_url?: string|null, document_back_url?: string|null, selfie_url?: string|null}  $payload
     */
    public function upsertForCandidate(User $user, array $payload): UserVerificationDocument
    {
        $type = (string) $payload['document_type'];
        if (!in_array($type, self::allowedDocumentTypes(), true)) {
            throw ValidationException::withMessages([
                'document_type' => ['Invalid document type for this endpoint.'],
            ]);
        }

        $existing = UserVerificationDocument::query()
            ->where('user_id', $user->id)
            ->where('document_type', $type)
            ->first();

        $previousUrls = null;
        if ($existing instanceof UserVerificationDocument) {
            $previousUrls = [
                'document_front_url' => $existing->document_front_url,
                'document_back_url' => $existing->document_back_url,
                'selfie_url' => $existing->selfie_url,
            ];
        }

        if ($existing instanceof UserVerificationDocument) {
            $status = (string) $existing->verification_status;
            if ($status === 'pending') {
                throw ValidationException::withMessages([
                    'document_type' => ['This document is under review and cannot be changed yet.'],
                ]);
            }
            if ($status === 'approved') {
                throw ValidationException::withMessages([
                    'document_type' => ['Approved documents cannot be changed.'],
                ]);
            }
        }

        $now = now();
        $attributes = [
            'document_number_masked' => $payload['document_number_masked'] ?? null,
            'document_front_url' => $payload['document_front_url'] ?? null,
            'document_back_url' => $payload['document_back_url'] ?? null,
            'selfie_url' => $payload['selfie_url'] ?? null,
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
            'submitted_at' => $now,
        ];

        if ($existing instanceof UserVerificationDocument) {
            $existing->fill($attributes);
            $existing->save();
            $fresh = $existing->fresh();
            $this->deleteReplacedIdVerificationFiles($user->id, $previousUrls, $attributes);

            return $fresh instanceof UserVerificationDocument ? $fresh : $existing;
        }

        /** @var UserVerificationDocument $created */
        $created = UserVerificationDocument::query()->create(
            array_merge(
                [
                    'user_id' => $user->id,
                    'document_type' => $type,
                ],
                $attributes
            )
        );

        return $created;
    }

    /**
     * @return LengthAwarePaginator<int, UserVerificationDocument>
     */
    public function paginatePendingForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        $candidateIds = User::query()->candidates()->select('id');

        return UserVerificationDocument::query()
            ->where('verification_status', 'pending')
            ->whereIn('user_id', $candidateIds)
            ->with(['user:id,uuid,first_name,last_name,email'])
            ->orderByDesc('submitted_at')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * @param  array{verification_status: string, rejection_reason?: string|null}  $payload
     */
    public function reviewDocument(
        UserVerificationDocument $document,
        int $reviewerUserId,
        array $payload
    ): UserVerificationDocument {
        if ((string) $document->verification_status !== 'pending') {
            throw ValidationException::withMessages([
                'verification_status' => ['Only pending documents can be reviewed.'],
            ]);
        }

        $newStatus = (string) $payload['verification_status'];
        if (!in_array($newStatus, ['approved', 'rejected', 'resubmission_required'], true)) {
            throw ValidationException::withMessages([
                'verification_status' => ['Invalid status.'],
            ]);
        }

        $document->verification_status = $newStatus;
        $document->verified_by = $reviewerUserId;
        $document->verified_at = now()->toDateTimeString();
        if ($newStatus === 'rejected') {
            $document->rejection_reason = (string) ($payload['rejection_reason'] ?? '');
        } else {
            $document->rejection_reason = $payload['rejection_reason'] ?? null;
        }
        $document->save();

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>  $new
     */
    private function deleteReplacedIdVerificationFiles(int $userId, ?array $previous, array $new): void
    {
        if ($previous === null) {
            return;
        }

        foreach (['document_front_url', 'document_back_url', 'selfie_url'] as $field) {
            $old = $previous[$field] ?? null;
            $next = $new[$field] ?? null;
            if (!is_string($old) || $old === '' || $old === $next) {
                continue;
            }

            $this->kycIdVerificationUpload->deleteStoredKeyIfOwned($old, $userId);
        }
    }
}
