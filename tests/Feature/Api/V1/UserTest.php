<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user to authenticate with
        $this->user = User::factory()->create();
    }

    /**
     * Test retrieving a paginated list of users.
     */
    public function test_it_lists_users_successfully(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['uuid', 'name', 'email'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully',
            ]);
    }

    /**
     * Test storing a new user successfully.
     */
    public function test_it_stores_a_user_successfully(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user', // Assuming a default role logic might exist or be required
        ];

        // Need to Mock permission/role if necessary, but assuming basic factory works.
        // If roles logic is complex, might need more setup.
        // Based on UserController, it uses UserService.

        $response = $this->actingAs($this->user)->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'message' => 'User created successfully',
        ]);

        $this->assertDatabaseHas('users', ['email' => $userData['email']]);
    }

    /**
     * Test validation failure when storing an invalid user.
     */
    public function test_it_returns_validation_error_when_storing_invalid_user(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/users', []); // Empty data

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonStructure(['errors']);
    }

    /**
     * Test retrieving a single user by UUID.
     */
    public function test_it_shows_a_user_successfully(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/users/{$targetUser->uuid}");

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'uuid' => $targetUser->uuid,
                'email' => $targetUser->email,
            ],
        ]);
    }

    /**
     * Test showing a user that does not exist returns 404.
     */
    public function test_it_returns_404_when_user_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/users/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
    }

    /**
     * Test updating a user successfully.
     */
    public function test_it_updates_a_user_successfully(): void
    {
        $targetUser = User::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'email' => $targetUser->email, // Keep same email
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/users/{$targetUser->uuid}", $updateData);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'User updated successfully',
        ]);

        $this->assertDatabaseHas('users', ['uuid' => $targetUser->uuid, 'name' => 'Updated Name']);
    }

    /**
     * Test validation failure during user update.
     */
    public function test_it_returns_validation_error_on_update(): void
    {
        $targetUser = User::factory()->create();
        $response = $this->actingAs($this->user)->putJson("/api/v1/users/{$targetUser->uuid}", [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ]);
    }

    /**
     * Test updating a non-existent user returns 404.
     */
    public function test_it_returns_404_on_update_missing_user(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/users/00000000-0000-0000-0000-000000000000', [
            'name' => 'New',
        ]);

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
    }

    /**
     * Test deleting a user successfully.
     */
    public function test_it_destroys_a_user_successfully(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/users/{$targetUser->uuid}");

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    }

    /**
     * Test deleting a non-existent user returns 404.
     */
    public function test_it_returns_404_on_destroy_missing_user(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/v1/users/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
    }

    /**
     * Test unauthenticated access to user list returns 401.
     */
    public function test_it_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401)->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
    }

    /**
     * Test calling an unsupported method on user endpoint returns 405.
     */
    public function test_it_returns_405_on_unsupported_method(): void
    {
        $targetUser = User::factory()->create();

        // POST to a GET route (/users/{user})
        $response = $this->actingAs($this->user)->postJson("/api/v1/users/{$targetUser->uuid}");

        $response->assertStatus(405)->assertJson([
            'success' => false,
            'message' => 'Method not allowed',
        ]);
    }

    /**
     * Test unexpected backend error returns 500.
     */
    public function test_it_returns_500_on_unexpected_error(): void
    {
        // Force an exception by calling a non-existent method on UserService during index
        $this->mock(\App\Services\UserService::class, function ($mock) {
            $mock->shouldReceive('getAllUsers')->andThrow(new \Exception('Test Server Error'));
        });

        $response = $this->actingAs($this->user)->getJson('/api/v1/users');

        $response->assertStatus(500)->assertJson([
            'success' => false,
        ]);

        // Depending on app.debug, message might change but structure should be consistent
        $response->assertJsonStructure(['success', 'message', 'code']);
    }
}
