<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use App\Services\PackagePermissionService;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
    $this->seed(PackageCatalogSeeder::class);
});

it('expires paid subscriptions past ends_at and activates Parichay free', function (): void {
    $candidate = User::query()->create([
        'first_name' => 'Expire',
        'last_name' => 'User',
        'email' => 'expire-to-parichay-' . uniqid('', true) . '@example.com',
        'password' => 'Password@123',
        'status' => 'active',
    ]);
    $candidate->assignRole('candidate');

    $talashId = (int) DB::table('packages')->where('code', 'TALASH_BASIC')->value('id');
    $parichayId = (int) DB::table('packages')->where('code', 'PARICHAY_FREE')->value('id');

    Subscription::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $candidate->id,
        'package_id' => $talashId,
        'subscription_status' => 'active',
        'started_at' => now()->subYear(),
        'ends_at' => now()->subDay(),
        'auto_renew' => false,
        'renewal_source' => 'manual',
    ]);

    app(PackagePermissionService::class)->syncCandidatePermissions($candidate->fresh());
    expect($candidate->fresh()->hasPermissionTo('candidate.browse_profiles.full'))->toBeTrue();

    Artisan::call('subscriptions:expire-to-parichay');

    $talash = Subscription::query()->where('user_id', $candidate->id)->where('package_id', $talashId)->first();
    expect($talash)->not->toBeNull();
    expect($talash->subscription_status)->toBe('expired');

    $parichay = Subscription::query()
        ->where('user_id', $candidate->id)
        ->where('package_id', $parichayId)
        ->where('subscription_status', 'active')
        ->first();
    expect($parichay)->not->toBeNull();

    $fresh = $candidate->fresh();
    expect($fresh->hasPermissionTo('candidate.browse_profiles.limited'))->toBeTrue();
    expect($fresh->hasPermissionTo('candidate.browse_profiles.full'))->toBeFalse();
});
