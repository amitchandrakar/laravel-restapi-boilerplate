<?php

declare(strict_types=1);
use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestAcceptedNotification;
use App\Notifications\ContactRequestReceivedNotification;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

const CONTACT_REQUEST_TEST_PW = 'Password@contact1';

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('redacts phone numbers on peer profiles until a contact request is accepted', function (): void {
    [$a, $b, $tokenA] = contactRequestTwoCandidatesWithTokens();
    $b->update(['phone' => '+919876543210']);

    $details = $this->withToken($tokenA)->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details');
    $details->assertStatus(200)->assertJsonPath('data.phone', null);

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', [
            'candidateUuid' => $b->uuid,
            'requestMessage' => 'Please share your number.',
        ])
        ->assertStatus(201);

    $detailsPending = $this->withToken($tokenA)->getJson(
        '/api/v1/admin/candidates/' . $b->uuid . '/profile-details'
    );
    $detailsPending->assertStatus(200)->assertJsonPath('data.phone', null);

    $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();
    $tokenB = contactRequestLoginToken($b->email);

    $this->withToken($tokenB)
        ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, [
            'decision' => 'accepted',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.requestStatus', 'accepted');

    $detailsOk = $this->withToken($tokenA)->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details');
    $detailsOk->assertStatus(200)->assertJsonPath('data.phone', '+919876543210');
    $detailsOk->assertJsonPath('data.sections.personalDetails.phone', '+919876543210');
});

it('notifies recipients on submit but only alerts senders after acceptance', function () {
    [$a, $b, $tokenA] = contactRequestTwoCandidatesWithTokens();
    $tokenB = contactRequestLoginToken($b->email);

    $beforeTo = $b->notifications()->count();
    $beforeFrom = $a->notifications()->count();

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', [
            'candidateUuid' => $b->uuid,
        ])
        ->assertStatus(201);

    $b->refresh();
    expect($b->notifications()->count())->toBe($beforeTo + 1);
    $received = $b
        ->notifications()
        ->where('type', ContactRequestReceivedNotification::class)
        ->orderByDesc('created_at')
        ->first();
    expect($received)->not->toBeNull();
    $payload = $received->data;
    if (is_string($payload)) {
        $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
    expect($payload['kind'] ?? null)->toBe('contact_request_received');

    $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();

    $this->withToken($tokenB)
        ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, [
            'decision' => 'rejected',
        ])
        ->assertStatus(200);

    $a->refresh();
    expect($a->notifications()->count())->toBe($beforeFrom);

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', [
            'candidateUuid' => $b->uuid,
        ])
        ->assertStatus(201);

    $row2 = ContactRequest::query()
        ->where('from_user_id', $a->id)
        ->where('to_user_id', $b->id)
        ->where('request_status', 'pending')
        ->firstOrFail();

    $this->withToken($tokenB)
        ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row2->uuid, [
            'decision' => 'accepted',
        ])
        ->assertStatus(200);

    $a->refresh();
    expect($a->notifications()->count())->toBe($beforeFrom + 1);
    $acceptedN = $a
        ->notifications()
        ->where('type', ContactRequestAcceptedNotification::class)
        ->orderByDesc('created_at')
        ->first();
    expect($acceptedN)->not->toBeNull();
    $fromPayload = $acceptedN->data;
    if (is_string($fromPayload)) {
        $fromPayload = json_decode($fromPayload, true, 512, JSON_THROW_ON_ERROR);
    }
    expect($fromPayload['kind'] ?? null)->toBe('contact_request_accepted');
});

it('rejects creating a second pending invitation to the same peer', function () {
    [$a, $b, $tokenA] = contactRequestTwoCandidatesWithTokens();

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
        ->assertStatus(201);

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
        ->assertStatus(422);
});

it('rejects invitations that target your own profile UUID', function () {
    [$a, , $tokenA] = contactRequestTwoCandidatesWithTokens();

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $a->uuid])
        ->assertStatus(422);
});

it('returns forbidden when a user who is not the recipient tries to settle a request', function () {
    [$a, $b, $tokenA] = contactRequestTwoCandidatesWithTokens();
    [, , $tokenC] = contactRequestTwoCandidatesWithTokens('other1-', 'other2-');

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
        ->assertStatus(201);

    $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();

    $this->withToken($tokenC)
        ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, ['decision' => 'accepted'])
        ->assertStatus(403);
});

it('returns forbidden when the sender lacks outbound contact permissions', function (): void {
    $email = 'no-send-' . uniqid('', true) . '@example.com';
    $this->postJson('/api/v1/auth/register', [
        'name' => 'No Send',
        'email' => $email,
        'password' => CONTACT_REQUEST_TEST_PW,
        'password_confirmation' => CONTACT_REQUEST_TEST_PW,
    ])->assertStatus(201);

    $user = User::query()->where('email', $email)->firstOrFail();
    $user->revokePermissionTo('candidate.send_contact_requests');

    [, $target] = contactRequestTwoCandidatesWithTokens('x-', 'y-');

    $tokenA = contactRequestLoginToken($email);

    $this->withToken($tokenA)
        ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $target->uuid])
        ->assertStatus(403);
});
/**
 * @return array{0: User, 1: User, 2: string}
 */
function contactRequestTwoCandidatesWithTokens(string $prefixA = 'cr-a-', string $prefixB = 'cr-b-'): array
{
    $emailA = $prefixA . uniqid('', true) . '@example.com';
    $emailB = $prefixB . uniqid('', true) . '@example.com';

    test()->postJson('/api/v1/auth/register', [
        'name' => 'Contact A',
        'email' => $emailA,
        'password' => CONTACT_REQUEST_TEST_PW,
        'password_confirmation' => CONTACT_REQUEST_TEST_PW,
    ]    )->assertStatus(201);

    test()->postJson('/api/v1/auth/register', [
        'name' => 'Contact B',
        'email' => $emailB,
        'password' => CONTACT_REQUEST_TEST_PW,
        'password_confirmation' => CONTACT_REQUEST_TEST_PW,
    ])->assertStatus(201);

    $a = User::query()->where('email', $emailA)->firstOrFail();
    $b = User::query()->where('email', $emailB)->firstOrFail();

    contactRequestSubscribeToTalash($a);
    contactRequestSubscribeToTalash($b);

    $tokenA = contactRequestLoginToken($emailA);

    return [$a->fresh(), $b->fresh(), $tokenA];
}
function contactRequestSubscribeToTalash(User $user): void
{
    $packageId = (int) DB::table('packages')->where('code', 'TALASH_BASIC')->value('id');
    $now = now();
    DB::table('subscriptions')->updateOrInsert(
        ['user_id' => $user->id, 'package_id' => $packageId],
        [
            'uuid' => (string) Str::uuid(),
            'subscription_status' => 'active',
            'started_at' => $now,
            'ends_at' => $now->copy()->addYear(),
            'auto_renew' => false,
            'renewal_source' => 'manual',
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    app(PackagePermissionService::class)->syncCandidatePermissions($user->fresh());
}
function contactRequestLoginToken(string $email): string
{
    $login = test()->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => CONTACT_REQUEST_TEST_PW,
    ]);
    $login->assertStatus(200);

    return (string) $login->json('data.token');
}
