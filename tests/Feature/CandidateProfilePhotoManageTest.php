<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\UserImageStorageUrl;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CandidateProfilePhotoManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_set_profile_photo(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'photo-set-profile@example.com');
        $uuidA = (string) Str::uuid();
        $uuidB = (string) Str::uuid();
        $this->insertPhoto($candidate->id, $uuidA, true);
        $this->insertPhoto($candidate->id, $uuidB, false);

        $this->actingAs($candidate, 'sanctum')
            ->patchJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$uuidB}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uuid', $uuidB)
            ->assertJsonPath('data.isProfilePhoto', true);

        $this->assertSame(0, (int) DB::table('user_images')->where('uuid', $uuidA)->value('is_profile_photo'));
        $this->assertSame(1, (int) DB::table('user_images')->where('uuid', $uuidB)->value('is_profile_photo'));
    }

    public function test_set_profile_photo_returns_404_for_unknown_uuid(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'photo-set-404@example.com');
        $unknown = (string) Str::uuid();

        $this->actingAs($candidate, 'sanctum')
            ->patchJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$unknown}")
            ->assertStatus(404);
    }

    public function test_candidate_can_soft_delete_own_photo(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'photo-delete@example.com');
        $uuid = (string) Str::uuid();
        $this->insertPhoto($candidate->id, $uuid, false);

        $this->actingAs($candidate, 'sanctum')
            ->deleteJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$uuid}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uuid', $uuid);

        $row = DB::table('user_images')->where('uuid', $uuid)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
    }

    public function test_delete_returns_404_for_other_users_photo(): void
    {
        $this->seed(RbacSeeder::class);
        $a = $this->createUserWithRole('candidate', 'photo-del-a@example.com');
        $b = $this->createUserWithRole('candidate', 'photo-del-b@example.com');
        $uuid = (string) Str::uuid();
        $this->insertPhoto($b->id, $uuid, false);

        $this->actingAs($a, 'sanctum')
            ->deleteJson("/api/v1/auth/candidate/{$a->uuid}/photos/{$uuid}")
            ->assertStatus(404);
    }

    public function test_guest_cannot_set_or_delete_photo(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'photo-guest@example.com');
        $uuid = (string) Str::uuid();
        $this->insertPhoto($candidate->id, $uuid, false);

        $this->patchJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$uuid}")->assertStatus(401);
        $this->deleteJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$uuid}")->assertStatus(401);
    }

    public function test_non_candidate_forbidden(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'photo-admin@example.com');
        $candidate = $this->createUserWithRole('candidate', 'photo-target@example.com');
        $uuid = (string) Str::uuid();
        $this->insertPhoto($candidate->id, $uuid, false);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/auth/candidate/{$candidate->uuid}/photos/{$uuid}")
            ->assertStatus(403);
    }

    public function test_candidate_cannot_manage_another_candidates_photos(): void
    {
        $this->seed(RbacSeeder::class);
        $a = $this->createUserWithRole('candidate', 'photo-cross-a@example.com');
        $b = $this->createUserWithRole('candidate', 'photo-cross-b@example.com');
        $uuid = (string) Str::uuid();
        $this->insertPhoto($b->id, $uuid, false);

        $this->actingAs($a, 'sanctum')
            ->patchJson("/api/v1/auth/candidate/{$b->uuid}/photos/{$uuid}")
            ->assertStatus(403);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function insertPhoto(int $userId, string $uuid, bool $isProfile): void
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
}
