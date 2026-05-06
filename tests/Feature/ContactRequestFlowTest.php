<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestAcceptedNotification;
use App\Notifications\ContactRequestReceivedNotification;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password@contact1';

    public function test_peer_profile_hides_phone_until_contact_request_accepted(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
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
        $tokenB = $this->loginToken($b->email);

        $this->withToken($tokenB)
            ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, [
                'decision' => 'accepted',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.requestStatus', 'accepted');

        $detailsOk = $this->withToken($tokenA)->getJson('/api/v1/admin/candidates/' . $b->uuid . '/profile-details');
        $detailsOk->assertStatus(200)->assertJsonPath('data.phone', '+919876543210');
        $detailsOk->assertJsonPath('data.sections.personalDetails.phone', '+919876543210');
    }

    public function test_to_user_gets_database_notification_on_request_from_user_gets_notification_on_accept_only(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        $tokenB = $this->loginToken($b->email);

        $beforeTo = $b->notifications()->count();
        $beforeFrom = $a->notifications()->count();

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', [
                'candidateUuid' => $b->uuid,
            ])
            ->assertStatus(201);

        $b->refresh();
        $this->assertSame($beforeTo + 1, $b->notifications()->count());
        $received = $b
            ->notifications()
            ->where('type', ContactRequestReceivedNotification::class)
            ->orderByDesc('created_at')
            ->first();
        $this->assertNotNull($received);
        $payload = $received->data;
        if (is_string($payload)) {
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }
        $this->assertSame('contact_request_received', $payload['kind'] ?? null);

        $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();

        $this->withToken($tokenB)
            ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, [
                'decision' => 'rejected',
            ])
            ->assertStatus(200);

        $a->refresh();
        $this->assertSame($beforeFrom, $a->notifications()->count());

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
        $this->assertSame($beforeFrom + 1, $a->notifications()->count());
        $acceptedN = $a
            ->notifications()
            ->where('type', ContactRequestAcceptedNotification::class)
            ->orderByDesc('created_at')
            ->first();
        $this->assertNotNull($acceptedN);
        $fromPayload = $acceptedN->data;
        if (is_string($fromPayload)) {
            $fromPayload = json_decode($fromPayload, true, 512, JSON_THROW_ON_ERROR);
        }
        $this->assertSame('contact_request_accepted', $fromPayload['kind'] ?? null);
    }

    public function test_cannot_duplicate_pending_request(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
            ->assertStatus(201);

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
            ->assertStatus(422);
    }

    public function test_cannot_request_self(): void
    {
        [$a, , $tokenA] = $this->twoCandidatesWithTokens();

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $a->uuid])
            ->assertStatus(422);
    }

    public function test_non_recipient_cannot_respond(): void
    {
        [$a, $b, $tokenA] = $this->twoCandidatesWithTokens();
        [, , $tokenC] = $this->twoCandidatesWithTokens('other1-', 'other2-');

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $b->uuid])
            ->assertStatus(201);

        $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();

        $this->withToken($tokenC)
            ->patchJson('/api/v1/auth/candidate/contact-requests/' . $row->uuid, ['decision' => 'accepted'])
            ->assertStatus(403);
    }

    public function test_forbidden_without_send_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $email = 'no-send-' . uniqid('', true) . '@example.com';
        $this->postJson('/api/v1/auth/register', [
            'name' => 'No Send',
            'email' => $email,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertStatus(201);

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->revokePermissionTo('candidate.send_contact_requests');

        [, $target] = $this->twoCandidatesWithTokens('x-', 'y-');

        $tokenA = $this->loginToken($email);

        $this->withToken($tokenA)
            ->postJson('/api/v1/auth/candidate/contact-requests', ['candidateUuid' => $target->uuid])
            ->assertStatus(201);
    }

    /**
     * @return array{0: User, 1: User, 2: string}
     */
    private function twoCandidatesWithTokens(string $prefixA = 'cr-a-', string $prefixB = 'cr-b-'): array
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $emailA = $prefixA . uniqid('', true) . '@example.com';
        $emailB = $prefixB . uniqid('', true) . '@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Contact A',
            'email' => $emailA,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertStatus(201);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Contact B',
            'email' => $emailB,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertStatus(201);

        $a = User::query()->where('email', $emailA)->firstOrFail();
        $b = User::query()->where('email', $emailB)->firstOrFail();

        $this->subscribeToTalash($a);
        $this->subscribeToTalash($b);

        $tokenA = $this->loginToken($emailA);

        return [$a->fresh(), $b->fresh(), $tokenA];
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
