<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\PackageCreatedEvent;
use App\Models\User;
use App\Notifications\PackageCreatedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPackageCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_permission_can_create_package(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-create@example.com');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/packages', [
            'name' => 'Gold Plan',
            'code' => 'gold_plan',
            'description' => 'Gold package for premium members.',
            'duration_unit' => 'year',
            'monthly_price' => 499,
            'yearly_price' => 4999,
            'currency' => 'inr',
            'is_active' => true,
            'is_default_registration' => true,
            'is_popular' => true,
            'sort_order' => 3,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'GOLD_PLAN')
            ->assertJsonPath('data.durationUnit', 'year')
            ->assertJsonPath('data.monthlyPrice', 499)
            ->assertJsonPath('data.yearlyPrice', 4999)
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonPath('data.isDefaultRegistration', true)
            ->assertJsonPath('data.isPopular', true)
            ->assertJsonPath('data.createdBy', $admin->id)
            ->assertJsonPath('data.updatedBy', $admin->id);

        $this->assertDatabaseHas('packages', [
            'name' => 'Gold Plan',
            'code' => 'GOLD_PLAN',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_candidate_without_permission_gets_forbidden(): void
    {
        $this->seed(RbacSeeder::class);
        $candidate = $this->createUserWithRole('candidate', 'candidate-create@example.com');

        $this->actingAs($candidate, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Starter',
                'code' => 'starter',
                'duration_unit' => 'month',
                'monthly_price' => 499,
                'yearly_price' => 4999,
            ])
            ->assertStatus(403);
    }

    public function test_validation_errors_for_duplicate_code_and_invalid_values(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-validation@example.com');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Silver',
                'code' => 'SILVER',
                'duration_unit' => 'month',
                'monthly_price' => 999,
                'yearly_price' => 9999,
            ])
            ->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Silver 2',
                'code' => 'SILVER',
                'duration_unit' => 'day',
                'monthly_price' => -10,
                'yearly_price' => -100,
            ])
            ->assertStatus(422);
    }

    public function test_event_and_notification_are_triggered_for_all_admins(): void
    {
        $this->seed(RbacSeeder::class);
        $adminOne = $this->createUserWithRole('admin', 'admin-one@example.com');
        $adminTwo = $this->createUserWithRole('admin', 'admin-two@example.com');

        $eventDispatched = false;
        Event::listen(PackageCreatedEvent::class, static function () use (&$eventDispatched): void {
            $eventDispatched = true;
        });
        Notification::fake();

        $this->actingAs($adminOne, 'sanctum')
            ->postJson('/api/v1/admin/packages', [
                'name' => 'Platinum',
                'code' => 'platinum',
                'duration_unit' => 'month',
                'monthly_price' => 7999,
                'yearly_price' => 69999,
            ])
            ->assertStatus(201);

        $this->assertTrue($eventDispatched);

        Notification::assertSentTo([$adminOne, $adminTwo], PackageCreatedNotification::class);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
