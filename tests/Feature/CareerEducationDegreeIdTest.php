<?php

declare(strict_types=1);
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('persists career education rows with explicit degree foreign keys', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(MasterDegreesOccupationsSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-career-deg@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-career-deg@example.com');

    $degreeId = (int) DB::table('degrees')->where('name', 'BE/BTech')->where('is_active', true)->value('id');
    expect($degreeId)->toBeGreaterThan(0);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/career-education', [
            'occupation' => 'Software Engineer',
            'employer' => 'Acme Tech',
            'income' => 1400000,
            'marital_status' => 'Single',
            'qualifications' => [
                [
                    'degree_id' => $degreeId,
                    'field_of_study' => 'Computer Science',
                    'institution_name' => 'NIT',
                    'year_of_graduation' => 2017,
                ],
            ],
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $candidate->id,
        'occupation' => 'Software Engineer',
        'employer' => 'Acme Tech',
    ]);

    $row = DB::table('user_education_details')->where('user_id', $candidate->id)->first();
    expect($row)->not->toBeNull();
    expect((int) $row->degree_id)->toBe($degreeId);
    expect($row->field_of_study)->toBe('Computer Science');
    expect($row->institution_name)->toBe('NIT');
    expect((int) $row->end_year)->toBe(2017);
});
