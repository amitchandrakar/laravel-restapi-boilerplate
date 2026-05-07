<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LifestylePartnerPreferencesFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_new_lifestyle_and_partner_preference_fields_and_profile_details_returns_them(): void
    {
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

        $this->assertIsArray($details);
        $this->assertSame('Night Owl', data_get($details, 'lifestyle.sleepPattern'));
        $this->assertSame(['Photography', 'Music'], data_get($details, 'lifestyle.interests'));
        $this->assertSame('Early Bird (Morning Person)', data_get($details, 'partnerPreferences.preferredSleepPattern'));
        $this->assertSame(['Travel'], data_get($details, 'partnerPreferences.preferredInterests'));

        $row = DB::table('user_partner_preferences')->where('user_id', $candidate->id)->first();
        $this->assertNotNull($row);
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
            'role_id' => (int) Role::query()->where('name', $role)->where('guard_name', 'web')->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
