<?php

declare(strict_types=1);

use App\Models\ContactRequest;
use App\Models\Favorite;
use App\Models\ProfileDoNotShow;
use App\Models\ProfileSpamReport;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

const MODERATION_TEST_PW = 'Password@moderation1';

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('allows a candidate to report another profile for spam', function (): void {
    [$reporter, $reported, $token] = moderationTwoCandidates();

    $this->withToken($token)
        ->postJson('/api/v1/app/auth/candidate/' . $reported->uuid . '/report-spam', [
            'reason' => 'Suspicious messages asking for money.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    expect(
        ProfileSpamReport::query()
            ->where('reporter_user_id', $reporter->id)
            ->where('reported_user_id', $reported->id)
            ->where('status', 'pending')
            ->exists()
    )->toBeTrue();
});

it('blocks login for spam-marked inactive accounts', function (): void {
    $user = $this->createUserWithRole('candidate', 'spam-login-' . uniqid('', true) . '@example.com');
    $user->update(['profile_status' => 'spam', 'status' => 'inactive']);

    $this->postJson('/api/v1/auth/login', [
        'username' => $user->email,
        'password' => 'Password@123',
    ])->assertStatus(401);
});

it('lists spam reports and resolves them for admins', function (): void {
    [$reporter, $reported] = moderationTwoCandidates();
    $report = ProfileSpamReport::query()->create([
        'reporter_user_id' => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason' => 'Fake profile',
        'status' => 'pending',
    ]);

    $admin = $this->createUserWithRole('admin', 'mod-admin-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/moderation-reports/spam')
        ->assertStatus(200)
        ->assertJsonPath('data.0.uuid', (string) $report->uuid)
        ->assertJsonPath('data.0.reportedPerson.uuid', (string) $reported->uuid);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/moderation-reports/spam/' . $report->uuid . '/mark-spammer')
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'action_taken');

    $reported->refresh();
    expect($reported->profile_status)->toBe('spam');
    expect($reported->status)->toBe('inactive');
});

it('dismisses spam reports and restores profile when appropriate', function (): void {
    [$reporter, $reported] = moderationTwoCandidates();
    $reported->update(['profile_status' => 'spam', 'status' => 'inactive', 'published_at' => now()]);

    $report = ProfileSpamReport::query()->create([
        'reporter_user_id' => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason' => 'False alarm',
        'status' => 'pending',
    ]);

    $admin = $this->createUserWithRole('admin', 'mod-dismiss-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/moderation-reports/spam/' . $report->uuid . '/mark-not-spammer')
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'dismissed');

    $reported->refresh();
    expect($reported->profile_status)->toBe('published');
    expect($reported->status)->toBe('active');
});

it('lists favorites for admin moderation and soft-deletes on unmark', function (): void {
    [$marker, $marked] = moderationTwoCandidates();
    $favorite = Favorite::query()->create([
        'user_id' => $marker->id,
        'favorite_user_id' => $marked->id,
        'source' => 'profile',
    ]);

    $admin = $this->createUserWithRole('admin', 'mod-fav-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/moderation-reports/favorites')
        ->assertStatus(200)
        ->assertJsonPath('data.0.uuid', (string) $favorite->uuid)
        ->assertJsonPath('data.0.markedPerson.uuid', (string) $marked->uuid);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/moderation-reports/favorites/' . $favorite->uuid . '/unmark')
        ->assertStatus(200);

    expect(Favorite::query()->where('id', $favorite->id)->whereNull('deleted_at')->exists())->toBeFalse();
});

it('lists contact requests and records admin resolution', function (): void {
    [$from, $to] = moderationTwoCandidates();
    $row = ContactRequest::query()->create([
        'from_user_id' => $from->id,
        'to_user_id' => $to->id,
        'request_message' => 'Please share contact.',
        'request_status' => 'pending',
    ]);

    $admin = $this->createUserWithRole('admin', 'mod-cr-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/moderation-reports/contact-requests')
        ->assertStatus(200)
        ->assertJsonPath('data.0.uuid', (string) $row->uuid)
        ->assertJsonPath('data.0.requestedPerson.uuid', (string) $to->uuid);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/moderation-reports/contact-requests/' . $row->uuid . '/mark-contacted')
        ->assertStatus(200)
        ->assertJsonPath('data.adminResolution', 'contacted');

    $row->refresh();
    expect($row->admin_resolution)->toBe('contacted');
    expect($row->admin_resolved_by)->toBe($admin->id);
});

it('lists dont-show-again marks and unmarks them for admins', function (): void {
    [$viewer, $hidden, $token] = moderationTwoCandidates();

    $this->withToken($token)
        ->postJson('/api/v1/app/auth/candidate/' . $hidden->uuid . '/dont-show-again')
        ->assertStatus(201);

    $row = ProfileDoNotShow::query()
        ->where('user_id', $viewer->id)
        ->where('hidden_user_id', $hidden->id)
        ->firstOrFail();

    $admin = $this->createUserWithRole('admin', 'mod-dns-' . uniqid('', true) . '@example.com');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/moderation-reports/dont-show-again')
        ->assertStatus(200)
        ->assertJsonPath('data.0.uuid', (string) $row->uuid)
        ->assertJsonPath('data.0.markedPerson.uuid', (string) $hidden->uuid);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/moderation-reports/dont-show-again/' . $row->uuid . '/unmark')
        ->assertStatus(200);

    expect(ProfileDoNotShow::query()->where('id', $row->id)->exists())->toBeFalse();
});

it('excludes admin-resolved contacted requests from member sent list', function (): void {
    [$from, $to, $token] = moderationTwoCandidates();
    $visible = ContactRequest::query()->create([
        'from_user_id' => $from->id,
        'to_user_id' => $to->id,
        'request_status' => 'pending',
    ]);
    $hidden = ContactRequest::query()->create([
        'from_user_id' => $from->id,
        'to_user_id' => $to->id,
        'request_status' => 'accepted',
        'admin_resolution' => 'contacted',
        'admin_resolved_at' => now(),
    ]);

    $response = $this->withToken($token)->getJson('/api/v1/app/auth/candidate/contact-requests');
    $response->assertStatus(200);

    $uuids = collect($response->json('data'))->pluck('uuid')->all();
    expect($uuids)->toContain((string) $visible->uuid);
    expect($uuids)->not->toContain((string) $hidden->uuid);
});

it('denies moderation report access without permissions', function (): void {
    $reviewer = $this->createUserWithRole('reviewer', 'mod-reviewer-' . uniqid('', true) . '@example.com');
    $reviewer->revokePermissionTo('admin.moderation_reports.view');

    $this->actingAs($reviewer, 'sanctum')->getJson('/api/v1/admin/moderation-reports/spam')->assertStatus(403);
});

/**
 * @return array{0: User, 1: User, 2: string}
 */
function moderationTwoCandidates(): array
{
    $emailA = 'mod-a-' . uniqid('', true) . '@example.com';
    $emailB = 'mod-b-' . uniqid('', true) . '@example.com';

    test()
        ->postJson('/api/v1/app/auth/register', [
            'name' => 'Mod A',
            'email' => $emailA,
            'password' => MODERATION_TEST_PW,
            'password_confirmation' => MODERATION_TEST_PW,
        ])
        ->assertStatus(201);

    test()
        ->postJson('/api/v1/app/auth/register', [
            'name' => 'Mod B',
            'email' => $emailB,
            'password' => MODERATION_TEST_PW,
            'password_confirmation' => MODERATION_TEST_PW,
        ])
        ->assertStatus(201);

    $a = User::query()->where('email', $emailA)->firstOrFail();
    $b = User::query()->where('email', $emailB)->firstOrFail();

    moderationSubscribeToTalash($a);
    moderationSubscribeToTalash($b);

    $tokenA = moderationLoginToken($emailA);

    return [$a->fresh(), $b->fresh(), $tokenA];
}

function moderationSubscribeToTalash(User $user): void
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
}

function moderationLoginToken(string $email): string
{
    $login = test()->postJson('/api/v1/auth/login', [
        'username' => $email,
        'password' => MODERATION_TEST_PW,
    ]);
    $login->assertStatus(200);

    return (string) $login->json('data.token');
}
