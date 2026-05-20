<?php

declare(strict_types=1);
use Database\Seeders\ChhattisgarhMasterGeoSeeder;
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Illuminate\Support\Facades\DB;

it('matches the canonical shape for public candidate profile options', function () {
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
    expect($data)->toBeArray();
    expect($data)->toHaveKey('surnames');
    expect($data)->toHaveKey('degrees');
    expect($data)->toHaveKey('heights');
    expect($data)->toHaveKey('bodyTypes');
    expect($data)->toHaveKey('complexions');
    expect($data)->toHaveKey('bloodGroups');
    expect($data)->toHaveKey('zodiacSigns');
    expect($data)->toHaveKey('diets');
    expect($data)->toHaveKey('sleepPatterns');
    expect($data)->toHaveKey('workingHours');
    expect($data)->toHaveKey('socialPersonalities');
    expect($data)->toHaveKey('dietaryPreferences');
    expect($data)->toHaveKey('drinkingHabits');
    expect($data)->toHaveKey('smokingHabits');
    expect($data)->toHaveKey('fitnessLevels');
    expect($data)->toHaveKey('travelStyles');
    expect($data)->toHaveKey('communicationStyles');
    expect($data)->toHaveKey('relationshipsWithFamily');
    expect($data)->toHaveKey('weekendPreferences');
    expect($data)->toHaveKey('interests');
    expect($data)->toHaveKey('movieGenres');
    expect($data)->toHaveKey('hobbies');
    expect($data)->toHaveKey('likes');
    expect($data)->toHaveKey('dislikes');
    expect($data)->toHaveKey('countries');

    expect($data['surnames'])->toHaveCount(1);
    expect($data['surnames'][0]['name'])->toBe('TestSurname');
    expect(count($data['degrees']))->toBeGreaterThanOrEqual(20);
    expect($data['degrees'][0])->toHaveKey('degreeType');

    expect($data['heights'])->toHaveCount(49);
    expect($data['heights'][0])->toBe(['value' => '4-0', 'label' => "4'0\""]);
    expect($data['heights'][48])->toBe(['value' => '8-0', 'label' => "8'0\""]);

    expect($data['bodyTypes'])->toHaveKey('male');
    expect($data['bodyTypes'])->toHaveKey('female');
    expect($data['bodyTypes']['male'])->toHaveCount(4);
    expect($data['bodyTypes']['female'])->toHaveCount(5);

    expect($data['complexions'])->toHaveCount(7);
    expect($data['bloodGroups'])->toHaveCount(9);
    expect($data['zodiacSigns'])->toHaveCount(12);
    expect($data['zodiacSigns'][0])->toHaveKey('iconUrl');
    expect($data['zodiacSigns'][0]['iconUrl'])->toEndWith('/images/zodiac/aries.svg');
    expect($data['diets'])->toHaveCount(5);
    expect($data['sleepPatterns'])->not->toBeEmpty();
    expect($data['workingHours'])->not->toBeEmpty();
    expect($data['interests'])->not->toBeEmpty();

    $countries = $data['countries'];
    expect($countries)->not->toBeEmpty();
    $in = collect($countries)->firstWhere('iso2', 'IN');
    expect($in)->toBeArray();
    expect($in)->toHaveKey('states');
    expect($in['states'])->not->toBeEmpty();
    $cg = collect($in['states'])->firstWhere('code', 'CG');
    expect($cg)->toBeArray();
    expect($cg)->toHaveKey('cities');
    expect($cg['cities'])->not->toBeEmpty();
});
