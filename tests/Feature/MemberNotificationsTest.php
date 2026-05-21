<?php

declare(strict_types=1);
use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestReceivedNotification;
use App\Notifications\ProfileViewedNotification;
use App\Services\MatchNotificationService;
use App\Services\MemberNotificationFeedService;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

const MEMBER_NOTIFICATIONS_TEST_PW = 'Password@notif1';

it('includes actionable new-match rows with unread metadata in the notifications list', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    $matchUuid = (string) Str::uuid();
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, $matchUuid, 88);

    $res = $this->withToken($tokenA)
        ->getJson('/api/v1/app/auth/notifications')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $items = $res->json('data');
    expect($items)->toBeArray();
    expect($items)->not->toBeEmpty();
    $first = $items[0];
    expect($first['kind'] ?? null)->toBe('new_match');
    expect($first['iconKey'] ?? null)->toBe('new_match');
    expect($first)->toHaveKey('profileImageUrl');
    expect($first['profileImageUrl'])->toBeString();
    expect($first['profileImageUrl'])->not->toBe('');
    expect($first['actions'] ?? null)->not->toBeEmpty();
    expect($first['actions'][0]['method'] ?? null)->toBe('GET');
    expect(
        str_contains((string) ($first['actions'][0]['path'] ?? ''), '/api/v1/app/auth/candidate/matches')
    )->toBeTrue();

    expect((int) $res->json('meta.unreadCount'))->toBeGreaterThanOrEqual(1);
});

it('embeds live contact-request status and timestamps on notification cards', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();

    $row = ContactRequest::query()->create([
        'from_user_id' => $b->id,
        'to_user_id' => $a->id,
        'request_message' => 'Hi!',
        'request_status' => 'pending',
    ]);
    $a->notify(new ContactRequestReceivedNotification($row));

    $res = $this->withToken($tokenA)->getJson('/api/v1/app/auth/notifications')->assertStatus(200);
    $items = (array) $res->json('data');
    expect($items)->not->toBeEmpty();

    $found = null;

    foreach ($items as $item) {
        if (($item['kind'] ?? null) === 'contact_request_received') {
            $found = $item;

            break;
        }
    }
    expect($found)->toBeArray();
    expect($found['contactRequestStatus'] ?? null)->toBe('pending');
    expect($found['contactRequestUpdatedAt'] ?? null)->not->toBeNull();
    expect($found['contactRequestUpdatedAt'])->toBeString();
});

it('returns aggregate unread counts from the notifications summary endpoint', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 70);

    $this->withToken($tokenA)
        ->getJson('/api/v1/app/auth/notifications/summary')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.unreadCount', fn($c) => (int) $c >= 1);
});

it('omits read notifications when clients request unread-only feeds', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 71);

    $nid = (string) $a->notifications()->orderByDesc('created_at')->value('id');
    expect($nid)->not->toBe('');
    $this->withToken($tokenA)
        ->patchJson('/api/v1/app/auth/notifications/' . $nid . '/read')
        ->assertStatus(200);

    $unread = $this->withToken($tokenA)->getJson('/api/v1/app/auth/notifications?unreadOnly=1')->json('data');
    expect($unread)->toBeArray();

    foreach ($unread as $item) {
        expect($item['id'] ?? null)->not->toBe($nid);
    }
});

it('supports marking one notification as read and clearing every unread item at once', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 72);
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 73);

    $ids = $a
        ->notifications()
        ->orderByDesc('created_at')
        ->limit(2)
        ->pluck('id')
        ->map(static fn($id): string => (string) $id)
        ->all();
    expect($ids)->toHaveCount(2);

    $this->withToken($tokenA)
        ->patchJson('/api/v1/app/auth/notifications/' . $ids[0] . '/read')
        ->assertStatus(200);

    expect($a->notifications()->whereKey($ids[0])->value('read_at'))->not->toBeNull();

    $this->withToken($tokenA)->postJson('/api/v1/app/auth/notifications/read-all')->assertStatus(200);

    $unread = $a
        ->notifications()
        ->whereNull('read_at')
        ->whereIn('data->kind', MemberNotificationFeedService::FEED_KINDS)
        ->count();
    expect($unread)->toBe(0);
});

it('returns forbidden when a user tries to mark another member\'s notification as read', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    $tokenB = memberNotificationsLoginToken($b->email);
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 74);

    $idOnB = (string) $b->notifications()->orderByDesc('created_at')->value('id');
    expect($idOnB)->not->toBe('');

    $this->withToken($tokenA)
        ->patchJson('/api/v1/app/auth/notifications/' . $idOnB . '/read')
        ->assertStatus(403)
        ->assertJsonPath('success', false);

    $this->withToken($tokenB)
        ->patchJson('/api/v1/app/auth/notifications/' . $idOnB . '/read')
        ->assertStatus(200);
});

it('hydrates a single feed item via the notification show endpoint', function () {
    [$a, $b, $tokenA] = memberNotificationsTwoCandidatesWithTokens();
    app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 75);
    $nid = (string) $a->notifications()->where('data->kind', 'new_match')->orderByDesc('created_at')->value('id');

    $this->withToken($tokenA)
        ->getJson('/api/v1/app/auth/notifications/' . $nid)
        ->assertStatus(200)
        ->assertJsonPath('data.kind', 'new_match')
        ->assertJsonPath('data.id', $nid);
});

it('deduplicates peer profile-view notifications within the configured cooldown window', function (): void {
    $a = memberNotificationsMakeCandidate('pv-a-' . uniqid('', true) . '@example.com');
    $b = memberNotificationsMakeCandidate('pv-b-' . uniqid('', true) . '@example.com');
    $tokenA = memberNotificationsLoginToken($a->email);

    $before = $b->notifications()->where('type', ProfileViewedNotification::class)->count();

    $this->withToken($tokenA)
        ->getJson('/api/v1/app/auth/candidate/' . $b->uuid . '/profile-details')
        ->assertStatus(200);
    $this->withToken($tokenA)
        ->getJson('/api/v1/app/auth/candidate/' . $b->uuid . '/profile-details')
        ->assertStatus(200);

    $b->refresh();
    $after = $b->notifications()->where('type', ProfileViewedNotification::class)->count();
    expect($after)->toBe($before + 1);

    $views = (int) DB::table('profile_views')
        ->where('viewer_user_id', $a->id)
        ->where('viewed_user_id', $b->id)
        ->count();
    expect($views)->toBe(2);
});
/**
 * @return array{0: User, 1: User, 2: string}
 */
function memberNotificationsTwoCandidatesWithTokens(): array
{
    $emailA = 'notif-a-' . uniqid('', true) . '@example.com';
    $emailB = 'notif-b-' . uniqid('', true) . '@example.com';

    test()
        ->postJson('/api/v1/app/auth/register', [
            'name' => 'Notif A',
            'email' => $emailA,
            'password' => MEMBER_NOTIFICATIONS_TEST_PW,
            'password_confirmation' => MEMBER_NOTIFICATIONS_TEST_PW,
        ])
        ->assertStatus(201);

    test()
        ->postJson('/api/v1/app/auth/register', [
            'name' => 'Notif B',
            'email' => $emailB,
            'password' => MEMBER_NOTIFICATIONS_TEST_PW,
            'password_confirmation' => MEMBER_NOTIFICATIONS_TEST_PW,
        ])
        ->assertStatus(201);

    $a = User::query()->where('email', $emailA)->firstOrFail();
    $b = User::query()->where('email', $emailB)->firstOrFail();

    memberNotificationsSubscribeToTalash($a);
    memberNotificationsSubscribeToTalash($b);

    return [$a->fresh(), $b->fresh(), memberNotificationsLoginToken($emailA)];
}
function memberNotificationsMakeCandidate(string $email): User
{
    /** @var User $user */
    $user = User::query()->create([
        'first_name' => 'Pv',
        'last_name' => 'Test',
        'email' => $email,
        'password' => MEMBER_NOTIFICATIONS_TEST_PW,
        'status' => 'active',
        'role_id' => (int) DB::table('roles')->where('name', 'candidate')->where('guard_name', 'web')->value('id'),
    ]);
    $user->assignRole('candidate');

    return $user;
}
function memberNotificationsSubscribeToTalash(User $user): void
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
function memberNotificationsLoginToken(string $email): string
{
    $login = test()->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => MEMBER_NOTIFICATIONS_TEST_PW,
    ]);
    $login->assertStatus(200);

    return (string) $login->json('data.token');
}
