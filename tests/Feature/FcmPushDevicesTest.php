<?php

declare(strict_types=1);

use App\Jobs\SendFcmPushForNotificationJob;
use App\Models\User;
use App\Models\UserPushDevice;
use App\Models\UserVerificationDocument;
use App\Notifications\KycReviewResultNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

function fcmLoginToken(User $user, string $password = 'Password@123'): string
{
    $login = test()->postJson('/api/v1/auth/login', [
        'username' => $user->email,
        'password' => $password,
    ]);
    $login->assertStatus(200);

    return (string) $login->json('data.token');
}

it('persists fcm device tokens on PUT /me/devices', function (): void {
    $user = $this->createUserWithRole('candidate', 'push-device-' . uniqid('', true) . '@example.com');
    $token = fcmLoginToken($user);

    $this->withToken($token)
        ->putJson('/api/v1/app/me/devices', [
            'fcm_token' => 'fcm-test-token-abc',
            'platform' => 'android',
            'device_id' => 'device-1',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.registered', true)
        ->assertJsonPath('data.stub', false);

    expect(
        UserPushDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', 'fcm-test-token-abc'))
            ->exists()
    )->toBeTrue();
});

it('unregisters a device token', function (): void {
    $user = $this->createUserWithRole('candidate', 'push-unreg-' . uniqid('', true) . '@example.com');
    UserPushDevice::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'fcm_token' => 'fcm-to-remove',
        'token_hash' => hash('sha256', 'fcm-to-remove'),
        'platform' => 'ios',
        'last_seen_at' => now(),
    ]);

    $token = fcmLoginToken($user);

    $this->withToken($token)
        ->deleteJson('/api/v1/app/me/devices', [
            'fcm_token' => 'fcm-to-remove',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.unregistered', true);

    expect(UserPushDevice::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('dispatches fcm push job after a member database notification is sent', function (): void {
    Bus::fake([SendFcmPushForNotificationJob::class]);

    $user = $this->createUserWithRole('candidate', 'push-kyc-' . uniqid('', true) . '@example.com');
    $doc = UserVerificationDocument::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'document_type' => 'aadhaar',
        'verification_status' => 'approved',
        'submitted_at' => now(),
        'verified_at' => now(),
    ]);

    $notification = new KycReviewResultNotification($doc);
    $user->notifyNow($notification);

    // Listener also runs via NotificationSent; assert job from that path.
    Bus::assertDispatched(SendFcmPushForNotificationJob::class, function (SendFcmPushForNotificationJob $job) use (
        $user
    ): bool {
        return $job->userId === (int) $user->id && ($job->notificationData['kind'] ?? null) === 'kyc_approved';
    });
});
