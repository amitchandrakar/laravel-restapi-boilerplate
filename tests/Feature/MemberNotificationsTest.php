<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestReceivedNotification;
use App\Notifications\ProfileViewedNotification;
use App\Services\MatchNotificationService;
use App\Services\MemberNotificationFeedService;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password@notif1';

    public function test_list_includes_new_match_with_actions_and_unread_meta(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        $matchUuid = (string) Str::uuid();
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, $matchUuid, 88);

        $res = $this->withToken($tokenA)
            ->getJson('/api/v1/auth/notifications')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $res->json('data');
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $first = $items[0];
        $this->assertSame('new_match', $first['kind'] ?? null);
        $this->assertSame('new_match', $first['iconKey'] ?? null);
        $this->assertArrayHasKey('profileImageUrl', $first);
        $this->assertIsString($first['profileImageUrl']);
        $this->assertNotSame('', $first['profileImageUrl']);
        $this->assertNotEmpty($first['actions'] ?? null);
        $this->assertSame('GET', $first['actions'][0]['method'] ?? null);
        $this->assertStringContainsString(
            '/api/v1/auth/candidate/matches',
            (string) ($first['actions'][0]['path'] ?? '')
        );

        $this->assertGreaterThanOrEqual(1, (int) $res->json('meta.unreadCount'));
    }

    public function test_contact_request_notifications_include_live_status_and_updated_at(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();

        $row = ContactRequest::query()->create([
            'from_user_id' => $b->id,
            'to_user_id' => $a->id,
            'request_message' => 'Hi!',
            'request_status' => 'pending',
        ]);
        $a->notify(new ContactRequestReceivedNotification($row));

        $res = $this->withToken($tokenA)->getJson('/api/v1/auth/notifications')->assertStatus(200);
        $items = (array) $res->json('data');
        $this->assertNotEmpty($items);

        $found = null;
        foreach ($items as $item) {
            if (($item['kind'] ?? null) === 'contact_request_received') {
                $found = $item;
                break;
            }
        }
        $this->assertIsArray($found);
        $this->assertSame('pending', $found['contactRequestStatus'] ?? null);
        $this->assertNotNull($found['contactRequestUpdatedAt'] ?? null);
        $this->assertIsString($found['contactRequestUpdatedAt']);
    }

    public function test_summary_returns_unread_count(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 70);

        $this->withToken($tokenA)
            ->getJson('/api/v1/auth/notifications/summary')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unreadCount', fn($c) => (int) $c >= 1);
    }

    public function test_unread_only_excludes_read_items(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 71);

        $nid = (string) $a->notifications()->orderByDesc('created_at')->value('id');
        $this->assertNotSame('', $nid);
        $this->withToken($tokenA)
            ->patchJson('/api/v1/auth/notifications/' . $nid . '/read')
            ->assertStatus(200);

        $unread = $this->withToken($tokenA)->getJson('/api/v1/auth/notifications?unreadOnly=1')->json('data');
        $this->assertIsArray($unread);
        foreach ($unread as $item) {
            $this->assertNotSame($nid, $item['id'] ?? null);
        }
    }

    public function test_mark_read_and_mark_all_read(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 72);
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 73);

        $ids = $a
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(2)
            ->pluck('id')
            ->map(static fn($id): string => (string) $id)
            ->all();
        $this->assertCount(2, $ids);

        $this->withToken($tokenA)
            ->patchJson('/api/v1/auth/notifications/' . $ids[0] . '/read')
            ->assertStatus(200);

        $this->assertNotNull($a->notifications()->whereKey($ids[0])->value('read_at'));

        $this->withToken($tokenA)->postJson('/api/v1/auth/notifications/read-all')->assertStatus(200);

        $unread = $a
            ->notifications()
            ->whereNull('read_at')
            ->whereIn('data->kind', MemberNotificationFeedService::FEED_KINDS)
            ->count();
        $this->assertSame(0, $unread);
    }

    public function test_cannot_mark_read_another_users_notification(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        $tokenB = $this->loginToken($b->email);
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 74);

        $idOnB = (string) $b->notifications()->orderByDesc('created_at')->value('id');
        $this->assertNotSame('', $idOnB);

        $this->withToken($tokenA)
            ->patchJson('/api/v1/auth/notifications/' . $idOnB . '/read')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->withToken($tokenB)
            ->patchJson('/api/v1/auth/notifications/' . $idOnB . '/read')
            ->assertStatus(200);
    }

    public function test_show_returns_single_feed_item(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        app(MatchNotificationService::class)->notifyBothUsersOfMatch($a, $b, (string) Str::uuid(), 75);
        $nid = (string) $a->notifications()->where('data->kind', 'new_match')->orderByDesc('created_at')->value('id');

        $this->withToken($tokenA)
            ->getJson('/api/v1/auth/notifications/' . $nid)
            ->assertStatus(200)
            ->assertJsonPath('data.kind', 'new_match')
            ->assertJsonPath('data.id', $nid);
    }

    public function test_profile_peer_view_records_row_and_notifies_once_per_dedupe_window(): void
    {
        $this->seed(RbacSeeder::class);
        $a = $this->makeCandidate('pv-a-' . uniqid('', true) . '@example.com');
        $b = $this->makeCandidate('pv-b-' . uniqid('', true) . '@example.com');
        $tokenA = $this->loginToken($a->email);

        $before = $b->notifications()->where('type', ProfileViewedNotification::class)->count();

        $this->withToken($tokenA)
            ->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details')
            ->assertStatus(200);
        $this->withToken($tokenA)
            ->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details')
            ->assertStatus(200);

        $b->refresh();
        $after = $b->notifications()->where('type', ProfileViewedNotification::class)->count();
        $this->assertSame($before + 1, $after);

        $views = (int) DB::table('profile_views')
            ->where('viewer_user_id', $a->id)
            ->where('viewed_user_id', $b->id)
            ->count();
        $this->assertSame(2, $views);
    }

    /**
     * @return array{0: User, 1: User, 2: string}
     */
    private function twoCandidatesWithTokens(): array
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $emailA = 'notif-a-' . uniqid('', true) . '@example.com';
        $emailB = 'notif-b-' . uniqid('', true) . '@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Notif A',
            'email' => $emailA,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertStatus(201);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Notif B',
            'email' => $emailB,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertStatus(201);

        $a = User::query()->where('email', $emailA)->firstOrFail();
        $b = User::query()->where('email', $emailB)->firstOrFail();

        $this->subscribeToTalash($a);
        $this->subscribeToTalash($b);

        return [$a->fresh(), $b->fresh(), $this->loginToken($emailA)];
    }

    private function makeCandidate(string $email): User
    {
        $this->seed(RbacSeeder::class);
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Pv',
            'last_name' => 'Test',
            'email' => $email,
            'password' => self::PASSWORD,
            'status' => 'active',
            'role_id' => (int) DB::table('roles')->where('name', 'candidate')->where('guard_name', 'web')->value('id'),
        ]);
        $user->assignRole('candidate');

        return $user;
    }

    private function subscribeToTalash(User $user): void
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

    private function loginToken(string $email): string
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => self::PASSWORD,
        ]);
        $login->assertStatus(200);

        return (string) $login->json('data.token');
    }
}
