<?php

declare(strict_types=1);

use App\Models\ContactRequest;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('stores and returns SPOC contacts on family background', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'spoc-owner@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/family-background', [
            'father_name' => 'Rajesh',
            'spoc_contacts' => [
                [
                    'name' => 'Uncle Ramesh',
                    'relation' => 'Uncle',
                    'contact_number' => '9876501234',
                ],
            ],
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('user_spoc_contacts', [
        'user_id' => $candidate->id,
        'name' => 'Uncle Ramesh',
        'relation' => 'Uncle',
        'contact_number' => '9876501234',
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('data.sections.familyBackground.spocContacts.0.name', 'Uncle Ramesh')
        ->assertJsonPath('data.sections.familyBackground.spocContacts.0.contactNumber', '9876501234');
});

it('hides SPOC contacts from peers until a contact request is accepted', function (): void {
    [$a, $b, $tokenA] = contactRequestTwoCandidatesWithTokens('spoc-a-', 'spoc-b-');

    DB::table('user_spoc_contacts')->insert([
        'uuid' => (string) Str::uuid(),
        'user_id' => $b->id,
        'name' => 'Father',
        'relation' => 'Father',
        'contact_number' => '9000011111',
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $before = $this->withToken($tokenA)->getJson('/api/v1/app/auth/candidate/' . $b->uuid . '/profile-details');
    $before->assertStatus(200)->assertJsonPath('data.sections.familyBackground.spocContacts', []);

    $this->withToken($tokenA)
        ->postJson('/api/v1/app/auth/candidate/contact-requests', [
            'candidateUuid' => $b->uuid,
        ])
        ->assertStatus(201);

    $row = ContactRequest::query()->where('from_user_id', $a->id)->where('to_user_id', $b->id)->firstOrFail();
    $tokenB = contactRequestLoginToken($b->email);

    $this->withToken($tokenB)
        ->patchJson('/api/v1/app/auth/candidate/contact-requests/' . $row->uuid, [
            'decision' => 'accepted',
        ])
        ->assertStatus(200);

    $after = $this->withToken($tokenA)->getJson('/api/v1/app/auth/candidate/' . $b->uuid . '/profile-details');
    $after
        ->assertStatus(200)
        ->assertJsonPath('data.sections.familyBackground.spocContacts.0.name', 'Father')
        ->assertJsonPath('data.sections.familyBackground.spocContacts.0.contactNumber', '9000011111');
});

it('stores and returns optional property details', function (): void {
    $candidate = $this->createUserWithRole('candidate', 'property-owner@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->patchJson('/api/v1/app/auth/candidate/profile/property-details', [
            'properties' => [
                [
                    'property_type' => 'shop',
                    'area_sq_ft' => 1200,
                    'city' => 'Raipur',
                    'state' => 'Chhattisgarh',
                    'country' => 'India',
                ],
                [
                    'property_type' => 'agricultural_field',
                    'area_sq_ft' => 5000,
                    'city' => 'Durg',
                    'state' => 'Chhattisgarh',
                    'country' => 'India',
                ],
            ],
        ])
        ->assertStatus(200);

    expect(DB::table('user_property_details')->where('user_id', $candidate->id)->count())->toBe(2);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/app/auth/candidate/profile/details')
        ->assertStatus(200)
        ->assertJsonPath('data.sections.propertyDetails.properties.0.propertyType', 'shop')
        ->assertJsonPath('data.sections.propertyDetails.properties.0.areaSqFt', 1200)
        ->assertJsonPath('data.sections.propertyDetails.properties.1.propertyType', 'agricultural_field');
});
