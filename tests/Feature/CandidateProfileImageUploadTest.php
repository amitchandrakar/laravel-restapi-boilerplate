<?php

declare(strict_types=1);
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('user_profile_images');
});

it('persists profile uploads with resized variants for authenticated candidates', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'img-upload-1@example.com');
    $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

    $response = $this->actingAs($candidate, 'sanctum')->post('/api/v1/app/auth/candidate/profile/photos/upload', [
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
    expect($row)->not->toBeNull();
    expect($row->image_storage_path)->toBe($mdKey);
    $this->assertStringContainsString('http', (string) $row->image_url);
    $this->assertStringContainsString('http', (string) $row->thumbnail_url);
    $this->assertStringContainsString('http', (string) $row->icon_url);
    expect((int) $row->is_profile_photo)->toBe(0);
});

it('returns validation errors on the sixth concurrent profile upload', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'img-upload-max@example.com');

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($candidate, 'sanctum')
            ->post('/api/v1/app/auth/candidate/profile/photos/upload', [
                'image' => UploadedFile::fake()->image("p{$i}.jpg", 200, 200),
            ])
            ->assertStatus(200);
    }

    $this->actingAs($candidate, 'sanctum')
        ->post('/api/v1/app/auth/candidate/profile/photos/upload', [
            'image' => UploadedFile::fake()->image('extra.jpg', 200, 200),
        ])
        ->assertStatus(422);
});

it('rejects profile uploads when the MIME type is not an image', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'img-upload-bad@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->post('/api/v1/app/auth/candidate/profile/photos/upload', [
            'image' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
        ])
        ->assertStatus(422);
});

it('returns forbidden when non-candidate accounts attempt profile uploads', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'img-upload-admin@example.com');

    $this->actingAs($admin, 'sanctum')
        ->post('/api/v1/app/auth/candidate/profile/photos/upload', [
            'image' => UploadedFile::fake()->image('x.jpg', 100, 100),
        ])
        ->assertStatus(403);
});
