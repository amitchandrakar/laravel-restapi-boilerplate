<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateProfileImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('user_profile_images');
    }

    public function test_candidate_can_upload_profile_image_and_variants_exist(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'img-upload-1@example.com');
        $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

        $response = $this->actingAs($candidate, 'sanctum')->post('/api/v1/auth/candidate/profile/photos/upload', [
            'image' => $file,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $uuid = (string) $response->json('data.uuid');
        $this->assertNotSame('', $uuid);

        $mdKey = $candidate->id . '/MD/' . $uuid . '.webp';
        $smKey = $candidate->id . '/SM/' . $uuid . '.webp';
        $origKey = $candidate->id . '/ORIGINAL/' . $uuid . '.webp';
        $iconKey = $candidate->id . '/ICON/' . $uuid . '.webp';

        Storage::disk('user_profile_images')->assertExists($mdKey);
        Storage::disk('user_profile_images')->assertExists($smKey);
        Storage::disk('user_profile_images')->assertExists($origKey);
        Storage::disk('user_profile_images')->assertExists($iconKey);

        $row = DB::table('user_images')->where('user_id', $candidate->id)->where('uuid', $uuid)->first();
        $this->assertNotNull($row);
        $this->assertSame($mdKey, $row->image_storage_path);
        $this->assertStringContainsString('http', (string) $row->image_url);
        $this->assertStringContainsString('http', (string) $row->thumbnail_url);
        $this->assertStringContainsString('http', (string) $row->icon_url);
        $this->assertSame(0, (int) $row->is_profile_photo);
    }

    public function test_sixth_upload_returns_validation_error(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'img-upload-max@example.com');

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($candidate, 'sanctum')
                ->post('/api/v1/auth/candidate/profile/photos/upload', [
                    'image' => UploadedFile::fake()->image("p{$i}.jpg", 200, 200),
                ])
                ->assertStatus(200);
        }

        $this->actingAs($candidate, 'sanctum')
            ->post('/api/v1/auth/candidate/profile/photos/upload', [
                'image' => UploadedFile::fake()->image('extra.jpg', 200, 200),
            ])
            ->assertStatus(422);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'img-upload-bad@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->post('/api/v1/auth/candidate/profile/photos/upload', [
                'image' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
            ])
            ->assertStatus(422);
    }

    public function test_non_candidate_cannot_upload(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'img-upload-admin@example.com');

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/auth/candidate/profile/photos/upload', [
                'image' => UploadedFile::fake()->image('x.jpg', 100, 100),
            ])
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
}
