<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserVerificationDocument;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycDocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    private const DOC_URL_FRONT = 'https://example.com/kyc/front.jpg';

    private const DOC_URL_BACK = 'https://example.com/kyc/back.jpg';

    public function test_candidate_can_submit_kyc_and_reviewer_can_approve(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'kyc-candidate@example.com');
        $reviewer = $this->createUserWithRole('reviewer', 'kyc-reviewer@example.com');

        $submit = $this->actingAs($candidate, 'sanctum')->putJson('/api/v1/auth/candidate/kyc/documents', [
            'document_type' => 'aadhaar',
            'document_number_masked' => 'XXXX-XXXX-1234',
            'document_front_url' => self::DOC_URL_FRONT,
            'document_back_url' => self::DOC_URL_BACK,
        ]);
        $submit->assertStatus(200)->assertJsonPath('data.verificationStatus', 'pending');
        $uuid = (string) $submit->json('data.uuid');
        $this->assertNotSame('', $uuid);

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/auth/candidate/kyc/documents', [
                'document_type' => 'aadhaar',
                'document_number_masked' => 'XXXX-XXXX-9999',
                'document_front_url' => self::DOC_URL_FRONT,
                'document_back_url' => self::DOC_URL_BACK,
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
        $this->assertNotNull($doc);
        $this->assertSame((int) $reviewer->id, (int) $doc->verified_by);
        $this->assertNotNull($doc->verified_at);
    }

    public function test_reject_requires_reason_and_non_pending_cannot_be_reviewed(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'kyc-reject@example.com');
        $reviewer = $this->createUserWithRole('reviewer', 'kyc-reject-reviewer@example.com');

        $uuid = (string) $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/auth/candidate/kyc/documents', [
                'document_type' => 'driving_license',
                'document_front_url' => self::DOC_URL_FRONT,
                'document_back_url' => self::DOC_URL_BACK,
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
    }

    public function test_candidate_can_resubmit_after_rejection(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'kyc-resubmit@example.com');
        $reviewer = $this->createUserWithRole('reviewer', 'kyc-resubmit-reviewer@example.com');

        $uuid = (string) $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/auth/candidate/kyc/documents', [
                'document_type' => 'aadhaar',
                'document_front_url' => self::DOC_URL_FRONT,
                'document_back_url' => self::DOC_URL_BACK,
            ])
            ->json('data.uuid');

        $this->actingAs($reviewer, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/kyc/documents/' . $uuid, [
                'verification_status' => 'rejected',
                'rejection_reason' => 'Bad',
            ])
            ->assertStatus(200);

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/auth/candidate/kyc/documents', [
                'document_type' => 'aadhaar',
                'document_front_url' => 'https://example.com/kyc/front2.jpg',
                'document_back_url' => 'https://example.com/kyc/back2.jpg',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.verificationStatus', 'pending');
    }

    public function test_invalid_document_type_is_rejected(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'kyc-invalid@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->putJson('/api/v1/auth/candidate/kyc/documents', [
                'document_type' => 'pan',
                'document_front_url' => self::DOC_URL_FRONT,
                'document_back_url' => self::DOC_URL_BACK,
            ])
            ->assertStatus(422);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
