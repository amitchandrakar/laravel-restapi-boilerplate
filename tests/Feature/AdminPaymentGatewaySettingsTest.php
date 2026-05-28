<?php

declare(strict_types=1);

use App\Models\PaymentGatewaySetting;
use Database\Seeders\RbacSeeder;

it('allows admins to update and fetch payment gateway settings with masked secrets', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-payments-settings@example.com');

    PaymentGatewaySetting::instance()->update([
        'live_key_secret' => 'secret-live',
        'sandbox_key_secret' => 'secret-sandbox',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/payments')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.hasLiveKeySecret', true)
        ->assertJsonMissingPath('data.liveKeySecret');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/payments', [
            'isEnabled' => true,
            'environment' => 'sandbox',
            'sandboxKeyId' => 'rzp_test',
            'checkoutOptionsJson' => '{"method":{"upi":true}}',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.isEnabled', true)
        ->assertJsonPath('data.sandboxKeyId', 'rzp_test')
        ->assertJsonPath('data.checkoutOptionsJson', '{"method":{"upi":true}}');
});

it('returns forbidden when a candidate updates payment settings', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-payments-settings@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/admin/settings/payments', ['isEnabled' => true])
        ->assertStatus(403);
});
