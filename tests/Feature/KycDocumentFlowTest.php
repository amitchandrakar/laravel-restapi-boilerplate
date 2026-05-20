<?php

declare(strict_types=1);
use App\Models\UserVerificationDocument;
use Database\Seeders\RbacSeeder;

const DOC_URL_FRONT = 'https://example.com/kyc/front.jpg';
const DOC_URL_BACK = 'https://example.com/kyc/back.jpg';

it('lets candidates submit KYC documents and reviewers finalize approval', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'kyc-candidate@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'kyc-reviewer@example.com');

    $submit = $this->actingAs($candidate, 'sanctum')->putJson('/api/v1/app/auth/candidate/kyc/documents', [
        'document_type' => 'aadhaar',
        'document_number_masked' => 'XXXX-XXXX-1234',
        'document_front_url' => DOC_URL_FRONT,
        'document_back_url' => DOC_URL_BACK,
    ]);
    $submit->assertStatus(200)->assertJsonPath('data.verificationStatus', 'pending');
    $uuid = (string) $submit->json('data.uuid');
    $this->assertNotSame('', $uuid);

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'aadhaar',
            'document_number_masked' => 'XXXX-XXXX-9999',
            'document_front_url' => DOC_URL_FRONT,
            'document_back_url' => DOC_URL_BACK,
        ])
        ->assertStatus(422);

    $this->actingAs($reviewer, 'sanctum')
        ->getJson('/api/v1/admin/candidates/kyc/pending')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'approved',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.verificationStatus', 'approved');

    $doc = UserVerificationDocument::query()->where('uuid', $uuid)->first();
    expect($doc)->not->toBeNull();
    expect((int) $doc->verified_by)->toBe((int) $reviewer->id);
    expect($doc->verified_at)->not->toBeNull();
});

it('requires rejection reasons and forbids reviewing documents that are not pending', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'kyc-reject@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'kyc-reject-reviewer@example.com');

    $uuid = (string) $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'driving_license',
            'document_front_url' => DOC_URL_FRONT,
            'document_back_url' => DOC_URL_BACK,
        ])
        ->json('data.uuid');

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'rejected',
        ])
        ->assertStatus(422);

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'rejected',
            'rejection_reason' => 'Illegible scan',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.verificationStatus', 'rejected');

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'approved',
        ])
        ->assertStatus(422);
});

it('permits resubmission after reviewers reject a pending document', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'kyc-resubmit@example.com');
    $reviewer = $this->createUserWithRole('reviewer', 'kyc-resubmit-reviewer@example.com');

    $uuid = (string) $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'aadhaar',
            'document_front_url' => DOC_URL_FRONT,
            'document_back_url' => DOC_URL_BACK,
        ])
        ->json('data.uuid');

    $this->actingAs($reviewer, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
            'verification_status' => 'rejected',
            'rejection_reason' => 'Bad',
        ])
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'aadhaar',
            'document_front_url' => 'https://example.com/kyc/front2.jpg',
            'document_back_url' => 'https://example.com/kyc/back2.jpg',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.verificationStatus', 'pending');
});

it('rejects KYC submissions with unknown document type codes', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'kyc-invalid@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/kyc/documents', [
            'document_type' => 'pan',
            'document_front_url' => DOC_URL_FRONT,
            'document_back_url' => DOC_URL_BACK,
        ])
        ->assertStatus(422);
});
