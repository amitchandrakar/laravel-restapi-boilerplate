<?php

declare(strict_types=1);
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;

it('walks admins through sectional saves before publishing the candidate profile', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-sections@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-sections@example.com');
    $candidateUuid = (string) $candidate->uuid;

    $this->actingAs($admin, 'sanctum')
        ->patchJson(
            '/api/v1/admin/candidates/' . $candidateUuid . '/sections/personal-details',
            validAdminPersonalDetailsPayload([
                'email' => 'candidate-sections@example.com',
            ])
        )
        ->assertStatus(200);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidateUuid . '/sections/photos', [
            'photos' => ['https://example.com/photo1.jpg'],
        ])
        ->assertStatus(200);

    foreach (
        [
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
});

it('merges extended basics columns into consolidated admin personal-detail saves', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-basics-body@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-basics-body@example.com');

    $this->actingAs($admin, 'sanctum')
        ->putJson(
            '/api/v1/admin/candidates/' . $candidate->uuid . '/sections/personal-details',
            validAdminPersonalDetailsPayload([
                'first_name' => 'Amit Candidate',
                'last_name' => 'Chandrakar',
                'email' => 'candidate-basics-body@example.com',
                'phone' => '9876543210',
                'marital_status' => 'single',
                'gender' => 'male',
                'height' => '5\'10',
                'manglik_status' => 'yes',
                'about_me' => 'Test bio',
            ])
        )
        ->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $candidate->id,
        'first_name' => 'Amit Candidate',
        'last_name' => 'Chandrakar',
        'phone' => '9876543210',
        'marital_status' => 'single',
    ]);
});

it('allows empowered candidates to edit their profile using admin sectional URLs', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-self-admin-sections@example.com');
    $uuid = (string) $candidate->uuid;

    expect($candidate->hasPermissionTo('admin.candidates.edit'))->toBeTrue();

    foreach (
        [
            'personal-details' => validAdminPersonalDetailsPayload([
                'email' => 'candidate-self-admin-sections@example.com',
            ]),
            'horoscope' => [],
            'location-family-roots' => [],
            'career-education' => [],
            'family-background' => [],
            'lifestyle' => [],
            'partner-preferences' => [],
        ] as $section => $body
    ) {
        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $uuid . '/sections/' . $section, $body)
            ->assertStatus(200);
    }
});

it('returns forbidden when editing another candidate through admin sectional routes', function () {
    $this->seed(RbacSeeder::class);
    $actor = $this->createUserWithRole('candidate', 'candidate-other-a@example.com');
    $other = $this->createUserWithRole('candidate', 'candidate-other-b@example.com');

    $this->actingAs($actor, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $other->uuid . '/sections/horoscope', [])
        ->assertStatus(403);
});

it('supports self-service basics saves plus authenticated profile-progress polling', function () {
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
});
/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validAdminPersonalDetailsPayload(array $overrides = []): array
{
    return array_merge(
        [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'placeholder@example.com',
            'phone' => '9990000000',
            'marital_status' => 'single',
            'gender' => 'male',
            'height' => '5ft 10in',
            'manglik_status' => 'no',
            'about_me' => 'About text',
        ],
        $overrides
    );
}
it('bulk saves multi-section payloads through `/admin/candidates/{uuid}/profile`', function () {
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
        ->assertJsonPath('data.sections.personalDetails.phone', '9999988888');
});

it('registers candidates end-to-end through `/admin/candidates/profile` bulk onboarding', function () {
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
});
