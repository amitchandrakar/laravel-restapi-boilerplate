<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('lists published candidates on the public search endpoint without auth', function (): void {
    $published = makePublicBrowseCandidate('public-browse-pub@example.com', true);
    makePublicBrowseCandidate('public-browse-draft@example.com', false);

    $res = $this->getJson('/api/v1/app/public/candidates/search');
    $res->assertStatus(200)->assertJsonPath('success', true);

    $uuids = collect($res->json('data'))->pluck('uuid')->all();
    expect($uuids)->toContain($published->uuid);
    expect($uuids)->not->toContain(User::query()->where('email', 'public-browse-draft@example.com')->value('uuid'));

    $first = collect($res->json('data'))->firstWhere('uuid', $published->uuid);
    expect($first)->toMatchArray([
        'uuid' => $published->uuid,
        'profileAccess' => 'public',
    ]);
    expect($first)->toHaveKeys(['fullName', 'age', 'profileImageUrl', 'educationSummary']);
    expect($first)->not->toHaveKeys([
        'dateOfBirth',
        'currentCity',
        'currentState',
        'occupation',
        'isFavorite',
        'phone',
        'email',
        'matchPercentage',
    ]);
});

it('returns a teaser public profile without sensitive fields', function (): void {
    $published = makePublicBrowseCandidate('public-detail-pub@example.com', true);

    $res = $this->getJson('/api/v1/app/public/candidates/' . $published->uuid);
    $res->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $published->uuid)
        ->assertJsonPath('data.profileAccess', 'public')
        ->assertJsonPath('data.phone', null)
        ->assertJsonPath('data.firstName', $published->first_name);

    $data = $res->json('data');
    expect($data)->not->toHaveKey('email');
    expect($data)->not->toHaveKey('dateOfBirth');
    expect($data['sections'] ?? [])->toHaveKeys(['photos', 'personalDetails', 'careerEducation']);
    expect($data['sections'] ?? [])->not->toHaveKeys([
        'horoscopeDetails',
        'locationFamilyRoots',
        'familyBackground',
        'propertyDetails',
        'lifestyle',
        'partnerPreferences',
    ]);
    expect($data['sections']['photos'])->toHaveCount(1);
    expect($data['sections']['personalDetails'])->toHaveKeys(['firstName', 'lastName', 'age', 'photoUrl']);
    expect($data['sections']['personalDetails'])->not->toHaveKeys(['email', 'phone', 'dateOfBirth']);
});

it('returns 404 for unpublished or unknown public candidate profiles', function (): void {
    $draft = makePublicBrowseCandidate('public-detail-draft@example.com', false);

    $this->getJson('/api/v1/app/public/candidates/' . $draft->uuid)->assertStatus(404);
    $this->getJson('/api/v1/app/public/candidates/00000000-0000-4000-8000-000000000099')->assertStatus(404);
});

it('still requires auth for member discovery search', function (): void {
    $this->getJson('/api/v1/app/auth/candidate/search')->assertStatus(401);
});

function makePublicBrowseCandidate(string $email, bool $published): User
{
    $roleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');

    /** @var User $user */
    $user = User::query()->create([
        'first_name' => 'Public',
        'last_name' => 'Candidate',
        'email' => $email,
        'password' => 'Password@123',
        'status' => 'active',
        'role_id' => $roleId,
        'current_city' => 'Raipur',
        'current_state' => 'Chhattisgarh',
        'occupation' => 'Engineer',
        'date_of_birth' => '1994-03-12',
        'profile_status' => $published ? 'published' : 'draft',
        'published_at' => $published ? now() : null,
    ]);
    $user->assignRole('candidate');

    return $user;
}
