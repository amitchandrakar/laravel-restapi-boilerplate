<?php

declare(strict_types=1);
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('stores parents and sibling metadata from family background submissions', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-fam-bg@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/family-background', [
            'father_name' => 'Rajesh',
            'father_occupation' => 'Business',
            'father_gotra' => 'Kashyap',
            'father_native_place' => 'Bilaspur',
            'mother_name' => 'Sunita',
            'mother_occupation' => 'Homemaker',
            'mother_gotra' => 'Kaushik',
            'mother_native_place' => 'Durg',
            'brothers_count' => 1,
            'sisters_count' => 1,
            'family_type' => 'Nuclear',
            'family_status' => 'Middle Class',
            'siblings' => [
                [
                    'name' => 'Ravi',
                    'age' => 40,
                    'occupation' => 'Business',
                    'marital_status' => 'Married',
                    'relation_type' => 'brother',
                ],
            ],
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $candidate->id,
        'father_name' => 'Rajesh',
        'mother_name' => 'Sunita',
        'brothers_count' => 1,
    ]);

    $sib = DB::table('user_siblings_details')->where('user_id', $candidate->id)->first();
    expect($sib)->not->toBeNull();
    expect($sib->name)->toBe('Ravi');
    expect((int) $sib->age)->toBe(40);
    expect($sib->relation_type)->toBe('brother');

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('data.sections.familyBackground.siblings.0.name', 'Ravi')
        ->assertJsonPath('data.sections.familyBackground.siblings.0.age', 40);
});

it('clears stored siblings whenever clients submit an empty siblings array', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-fam-clear@example.com');

    DB::table('user_siblings_details')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'name' => 'Temp',
        'relation_type' => 'sister',
        'sort_order' => 0,
        'is_elder' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/family-background', [
            'siblings' => [],
        ])
        ->assertStatus(200);

    expect(DB::table('user_siblings_details')->where('user_id', $candidate->id)->count())->toBe(0);
});
