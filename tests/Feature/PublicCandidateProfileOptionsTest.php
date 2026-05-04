<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicCandidateProfileOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_candidate_profile_options_returns_expected_shape(): void
    {
        $now = now();
        DB::table('surnames')->insert([
            ['name' => 'TestSurname', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->seed(MasterDegreesOccupationsSeeder::class);
        $this->seed(ChhattisgarhMasterGeoSeeder::class);

        $response = $this->getJson('/api/v1/public/candidate-profile-options')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('surnames', $data);
        $this->assertArrayHasKey('degrees', $data);
        $this->assertArrayHasKey('heights', $data);
        $this->assertArrayHasKey('bodyTypes', $data);
        $this->assertArrayHasKey('complexions', $data);
        $this->assertArrayHasKey('bloodGroups', $data);
        $this->assertArrayHasKey('zodiacSigns', $data);
        $this->assertArrayHasKey('diets', $data);
        $this->assertArrayHasKey('countries', $data);

        $this->assertCount(1, $data['surnames']);
        $this->assertSame('TestSurname', $data['surnames'][0]['name']);
        $this->assertGreaterThanOrEqual(20, count($data['degrees']));
        $this->assertArrayHasKey('degreeType', $data['degrees'][0]);

        $this->assertCount(49, $data['heights']);
        $this->assertSame(['value' => '4-0', 'label' => "4'0\""], $data['heights'][0]);
        $this->assertSame(['value' => '8-0', 'label' => "8'0\""], $data['heights'][48]);

        $this->assertArrayHasKey('male', $data['bodyTypes']);
        $this->assertArrayHasKey('female', $data['bodyTypes']);
        $this->assertCount(4, $data['bodyTypes']['male']);
        $this->assertCount(5, $data['bodyTypes']['female']);

        $this->assertCount(7, $data['complexions']);
        $this->assertCount(9, $data['bloodGroups']);
        $this->assertCount(12, $data['zodiacSigns']);
        $this->assertArrayHasKey('iconUrl', $data['zodiacSigns'][0]);
        $this->assertStringEndsWith('/images/zodiac/aries.svg', $data['zodiacSigns'][0]['iconUrl']);
        $this->assertCount(5, $data['diets']);

        $countries = $data['countries'];
        $this->assertNotEmpty($countries);
        $in = collect($countries)->firstWhere('iso2', 'IN');
        $this->assertIsArray($in);
        $this->assertArrayHasKey('states', $in);
        $this->assertNotEmpty($in['states']);
        $cg = collect($in['states'])->firstWhere('code', 'CG');
        $this->assertIsArray($cg);
        $this->assertArrayHasKey('cities', $cg);
        $this->assertArrayHasKey('districts', $cg);
        $this->assertNotEmpty($cg['cities']);
        $this->assertNotEmpty($cg['districts']);
        $districtWithVillage = collect($cg['districts'])->first(
            static fn(array $d): bool => isset($d['villages']) && $d['villages'] !== []
        );
        $this->assertNotNull($districtWithVillage, 'Expected at least one district with seeded villages');
        $this->assertArrayHasKey('id', $districtWithVillage);
        $this->assertArrayHasKey('name', $districtWithVillage);
    }
}
