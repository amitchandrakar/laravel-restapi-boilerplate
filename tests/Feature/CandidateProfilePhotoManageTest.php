<?php

declare(strict_types=1);
use App\Support\UserImageStorageUrl;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('sets the owning candidate\'s active profile photo from their gallery UUID', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photo-set-profile@example.com');
    $uuidA = (string) Str::uuid();
    $uuidB = (string) Str::uuid();
    insertPhoto($candidate->id, $uuidA, true);
    insertPhoto($candidate->id, $uuidB, false);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$uuidB}")
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $uuidB)
        ->assertJsonPath('data.isProfilePhoto', true);

    expect((int) DB::table('user_images')->where('uuid', $uuidA)->value('is_profile_photo'))->toBe(0);
    expect((int) DB::table('user_images')->where('uuid', $uuidB)->value('is_profile_photo'))->toBe(1);
});

it('returns not found when selecting a profile photo UUID that cannot be resolved', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photo-set-404@example.com');
    $unknown = (string) Str::uuid();

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$unknown}")
        ->assertStatus(404);
});

it('supports soft deleting the authenticated candidate\'s profile photo', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photo-delete@example.com');
    $uuid = (string) Str::uuid();
    insertPhoto($candidate->id, $uuid, false);

    $this->actingAs($candidate, 'sanctum')
        ->deleteJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$uuid}")
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $uuid);

    $row = DB::table('user_images')->where('uuid', $uuid)->first();
    expect($row)->not->toBeNull();
    expect($row->deleted_at)->not->toBeNull();
});

it('returns not found when deleting photo rows owned by someone else', function () {
    $this->seed(RbacSeeder::class);
    $a = $this->createUserWithRole('candidate', 'photo-del-a@example.com');
    $b = $this->createUserWithRole('candidate', 'photo-del-b@example.com');
    $uuid = (string) Str::uuid();
    insertPhoto($b->id, $uuid, false);

    $this->actingAs($a, 'sanctum')
        ->deleteJson("/api/v1/app/auth/candidate/{$a->uuid}/photos/{$uuid}")
        ->assertStatus(404);
});

it('requires authentication before mutating candidate profile photos', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'photo-guest@example.com');
    $uuid = (string) Str::uuid();
    insertPhoto($candidate->id, $uuid, false);

    $this->patchJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$uuid}")->assertStatus(401);
    $this->deleteJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$uuid}")->assertStatus(401);
});

it('returns forbidden when callers without the candidate role mutate profile photos', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'photo-admin@example.com');
    $candidate = $this->createUserWithRole('candidate', 'photo-target@example.com');
    $uuid = (string) Str::uuid();
    insertPhoto($candidate->id, $uuid, false);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/app/auth/candidate/{$candidate->uuid}/photos/{$uuid}")
        ->assertStatus(403);
});

it('returns forbidden when a candidate tries to manage another member\'s gallery', function () {
    $this->seed(RbacSeeder::class);
    $a = $this->createUserWithRole('candidate', 'photo-cross-a@example.com');
    $b = $this->createUserWithRole('candidate', 'photo-cross-b@example.com');
    $uuid = (string) Str::uuid();
    insertPhoto($b->id, $uuid, false);

    $this->actingAs($a, 'sanctum')
        ->patchJson("/api/v1/app/auth/candidate/{$b->uuid}/photos/{$uuid}")
        ->assertStatus(403);
});
function insertPhoto(int $userId, string $uuid, bool $isProfile): void
{
    $md = $userId . '/MD/' . $uuid . '.webp';
    $sm = $userId . '/SM/' . $uuid . '.webp';
    DB::table('user_images')->insert([
        'uuid' => $uuid,
        'user_id' => $userId,
        'image_type' => 'profile',
        'image_storage_path' => $md,
        'image_url' => UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($md) ?? $md) ?? $md,
        'thumbnail_url' => UserImageStorageUrl::toAbsoluteHttpUrl(UserImageStorageUrl::publicUrl($sm) ?? $sm) ?? $sm,
        'icon_url' => null,
        'is_profile_photo' => $isProfile,
        'sort_order' => 0,
        'is_active' => true,
        'uploaded_by' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
