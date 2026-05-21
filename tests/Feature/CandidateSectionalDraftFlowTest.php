<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RbacSeeder;

it('walks candidates through sectional saves before publishing the profile', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-sections@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson(
            '/api/v1/app/auth/candidate/profile/personal-details',
            validAdminPersonalDetailsPayload([
                'email' => 'candidate-sections@example.com',
            ])
        )
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/photos', [
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
        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/v1/app/auth/candidate/profile/' . $sectionPath, [])
            ->assertStatus(200);
    }

    $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/v1/app/auth/candidate/profile/publish')
        ->assertStatus(200)
        ->assertJsonPath('data.published', true);
});

it('merges extended basics columns into consolidated personal-detail saves', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-basics-body@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson(
            '/api/v1/app/auth/candidate/profile/personal-details',
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

it('allows candidates to edit their profile through app sectional URLs', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-self-app-sections@example.com');

    foreach (
        [
            'personal-details' => validAdminPersonalDetailsPayload([
                'email' => 'candidate-self-app-sections@example.com',
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
            ->patchJson('/api/v1/app/auth/candidate/profile/' . $section, $body)
            ->assertStatus(200);
    }
});

it('supports self-service basics saves plus authenticated profile-progress polling', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-self-sections@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/basics', [
            'email' => 'candidate-self-sections@example.com',
            'phone' => '9999900000',
        ])
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/progress')
        ->assertStatus(200)
        ->assertJsonPath('data.profileStatus', 'draft');
});

it('bulk saves multi-section payloads through app profile details', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-full-profile@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/app/auth/candidate/profile/details', [
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

it('registers candidates via admin create then app profile sections', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-complete-one-url@example.com');

    $create = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/candidates', [
        'first_name' => 'Single',
        'last_name' => 'Url',
        'email' => 'candidate-one-url@example.com',
        'phone' => '9999911111',
        'gender' => 'male',
        'marital_status' => 'single',
    ]);
    $create->assertStatus(201);

    $candidate = User::query()->where('email', 'candidate-one-url@example.com')->firstOrFail();

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/lifestyle', [
            'diet' => 'Vegetarian',
        ])
        ->assertStatus(200);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('data.uuid', $candidate->uuid);
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
