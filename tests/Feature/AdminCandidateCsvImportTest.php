<?php

declare(strict_types=1);

use App\Jobs\ImportCandidatesFromCsvJob;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('imports valid CSV rows and reports duplicate emails', function (): void {
    $admin = $this->createUserWithRole('admin', 'admin-import-' . uniqid('', true) . '@example.com');
    $existingEmail = 'existing-' . uniqid('', true) . '@example.com';
    $this->createUserWithRole('candidate', $existingEmail);

    $newEmail = 'imported-' . uniqid('', true) . '@example.com';
    $csv = "email,first_name,last_name,phone,gender\n";
    $csv .= "{$existingEmail},Dup,User,,\n";
    $csv .= "{$newEmail},Imported,User,9998887777,male\n";

    $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

    $response = $this->actingAs($admin, 'sanctum')->post(
        '/api/v1/admin/candidates/import',
        ['file' => $file],
        [
            'Accept' => 'application/json',
        ]
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.queued', false)
        ->assertJsonPath('data.summary.created', 1)
        ->assertJsonPath('data.summary.skipped', 1);

    expect(User::query()->where('email', $newEmail)->exists())->toBeTrue();
});

it('queues large CSV imports', function (): void {
    Queue::fake();

    $admin = $this->createUserWithRole('admin', 'admin-import-queue-' . uniqid('', true) . '@example.com');

    $csv = "email,first_name\n";

    for ($i = 0; $i < 201; $i++) {
        $csv .= 'bulk-' . $i . '-' . uniqid('', true) . '@example.com,User' . $i . "\n";
    }

    $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

    $this->actingAs($admin, 'sanctum')
        ->post(
            '/api/v1/admin/candidates/import',
            ['file' => $file],
            [
                'Accept' => 'application/json',
            ]
        )
        ->assertStatus(202)
        ->assertJsonPath('data.queued', true)
        ->assertJsonStructure(['data' => ['import_id', 'total_rows']]);

    Queue::assertPushed(ImportCandidatesFromCsvJob::class);
});
