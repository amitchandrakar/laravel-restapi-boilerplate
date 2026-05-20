<?php

declare(strict_types=1);
use App\Events\PackageCreatedEvent;
use App\Notifications\PackageCreatedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('allows admins with permission to create a package', function () {
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
});

it('returns forbidden when a candidate creates a package without permission', function () {
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
});

it('reports validation errors when package codes collide or payloads are invalid', function () {
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
});

it('notifies administrators when a package is created', function () {
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

    expect($eventDispatched)->toBeTrue();

    Notification::assertSentTo([$adminOne, $adminTwo], PackageCreatedNotification::class);
});
