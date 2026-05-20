<?php

declare(strict_types=1);
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;

it('persists lifestyle and partner-preference deltas and echoes them via profile-details reads', function () {
    $this->seed(RbacSeeder::class);

    $admin = $this->createUserWithRole('admin', 'admin-lifestyle-pref@example.com');
    $candidate = $this->createUserWithRole('candidate', 'candidate-lifestyle-pref@example.com');

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/lifestyle', [
            'sleep_pattern' => 'Night Owl',
            'working_hours' => 'Flexible Hours',
            'social_personality' => 'Ambivert',
            'dietary_preferences' => 'Vegetarian',
            'drinking_habits' => 'Never',
            'smoking_habits' => 'Non-smoker',
            'fitness_level' => 'Moderately Active',
            'travel_style' => 'Road Trip Lover',
            'communication_style' => 'Humorous',
            'relationship_with_family' => 'Very Close',
            'weekend_preference' => 'Family Time',
            'interests' => ['Photography', 'Music'],
            'movie_genres' => ['Comedy', 'Drama'],
            'hobbies' => ['Cycling'],
            'likes' => ['Home-cooked meals'],
            'dislikes' => ['Negativity'],
        ])
        ->assertStatus(200);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/partner-preferences', [
            'preferred_sleep_pattern' => 'Early Bird (Morning Person)',
            'preferred_working_hours' => 'Standard 9-to-5',
            'preferred_social_personality' => 'Introvert',
            'preferred_dietary_preferences' => 'Non-Vegetarian',
            'preferred_drinking_habits' => 'Occasionally',
            'preferred_smoking_habits' => 'Non-smoker',
            'preferred_fitness_level' => 'Fitness Enthusiast',
            'preferred_travel_style' => 'Budget Traveler',
            'preferred_communication_style' => 'Soft-spoken',
            'preferred_relationship_with_family' => 'Close-knit Family',
            'preferred_weekend_preference' => 'Staying Home',
            'preferred_interests' => ['Travel'],
            'preferred_movie_genres' => ['Action/Adventure'],
            'preferred_hobbies' => ['Trekking/Hiking'],
            'preferred_likes' => ['Meaningful Conversations'],
            'preferred_dislikes' => ['Rudeness/Lack of Manners'],
        ])
        ->assertStatus(200);

    $details = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/candidates/' . $candidate->uuid . '/profile-details')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->json('data.sections');

    expect($details)->toBeArray();
    expect(data_get($details, 'lifestyle.sleepPattern'))->toBe('Night Owl');
    expect(data_get($details, 'lifestyle.interests'))->toBe(['Photography', 'Music']);
    expect(data_get($details, 'partnerPreferences.preferredSleepPattern'))->toBe('Early Bird (Morning Person)');
    expect(data_get($details, 'partnerPreferences.preferredInterests'))->toBe(['Travel']);

    $row = DB::table('user_partner_preferences')->where('user_id', $candidate->id)->first();
    expect($row)->not->toBeNull();
});
