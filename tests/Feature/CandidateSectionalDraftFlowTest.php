<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateSectionalDraftFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_candidate_sections_and_publish(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-sections@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-sections@example.com');
        $candidateUuid = (string) $candidate->uuid;

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidateUuid . '/sections/basics', [
                'email' => 'candidate-sections@example.com',
            ])
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidateUuid . '/sections/photos', [
                'photos' => ['https://example.com/photo1.jpg'],
            ])
            ->assertStatus(200);

        foreach (
            [
                'personal-details',
                'horoscope',
                'location-family-roots',
                'career-education',
                'family-background',
                'lifestyle',
                'partner-preferences',
            ] as $sectionPath
        ) {
            $this->actingAs($admin, 'sanctum')
                ->patchJson('/api/v1/admin/candidates/' . $candidateUuid . '/sections/' . $sectionPath, [])
                ->assertStatus(200);
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/candidates/' . $candidateUuid . '/publish')
            ->assertStatus(200)
            ->assertJsonPath('data.published', true);
    }

    public function test_candidate_can_save_own_section_and_check_progress(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-self-sections@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/v1/auth/candidate/profile/basics', [
                'email' => 'candidate-self-sections@example.com',
                'phone' => '9999900000',
            ])
            ->assertStatus(200);

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/auth/candidate/profile/progress')
            ->assertStatus(200)
            ->assertJsonPath('data.profileStatus', 'draft');
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_save_full_profile_in_one_shot(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-full-profile@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-full-profile@example.com');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile', [
                'basics' => [
                    'email' => 'candidate-full-profile@example.com',
                    'phone' => '9999988888',
                ],
                'photos' => [
                    'photos' => ['https://example.com/full-profile-photo.jpg'],
                ],
                'personal_details' => [
                    'first_name' => 'Full',
                    'last_name' => 'Profile',
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.sections.basics.phone', '9999988888');
    }

    public function test_admin_can_save_complete_profile_through_single_url(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-complete-one-url@example.com');

        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/v1/admin/candidates/profile', [
            'password' => 'Password@123',
            'basics' => [
                'email' => 'candidate-one-url@example.com',
                'phone' => '9999911111',
            ],
            'personal_details' => [
                'first_name' => 'Single',
                'last_name' => 'Url',
            ],
            'lifestyle' => [
                'diet' => 'Vegetarian',
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('data.email', 'candidate-one-url@example.com');
    }
}
