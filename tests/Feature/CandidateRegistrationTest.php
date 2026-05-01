<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CandidateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password@reg1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);
    }

    public function test_registration_options_returns_packages_and_surnames(): void
    {
        $response = $this->getJson('/api/v1/auth/registration');
        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'packages' => [['uuid', 'name', 'code', 'monthlyPrice', 'yearlyPrice', 'isDefaultRegistration']],
                    'surnames' => [['id', 'name']],
                ],
            ]);

        $names = collect($response->json('data.surnames'))->pluck('name')->all();
        $this->assertContains('Chandrakar', $names);
    }

    public function test_register_candidate_creates_user_subscription_and_permissions(): void
    {
        $packageUuid = (string) Package::query()->where('code', 'TALASH_BASIC')->value('uuid');
        $this->assertNotEmpty($packageUuid);

        $email = 'candidate-reg-' . uniqid('', true) . '@example.com';

        $response = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Test',
            'last_name' => 'Chandrakar',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1995-06-15',
            'phone' => '9876543210',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true)->assertJsonPath('data.token_type', 'Bearer');

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('candidate'));
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('Chandrakar', $user->last_name);
        $this->assertTrue($user->getAllPermissions()->pluck('name')->contains('candidate.browse_profiles.full'));

        $packageId = (int) Package::query()->where('code', 'TALASH_BASIC')->value('id');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'subscription_status' => 'active',
        ]);
    }

    public function test_register_candidate_rejects_unknown_package_uuid(): void
    {
        $response = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Test',
            'last_name' => 'Chandrakar',
            'email' => 'bad-pkg-' . uniqid('', true) . '@example.com',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone' => '9876543211',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => '00000000-0000-4000-8000-000000000001',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_candidate_rejects_last_name_not_in_surnames(): void
    {
        $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');

        $response = $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'Test',
            'last_name' => 'UnknownSurnameXYZ',
            'email' => 'bad-surname-' . uniqid('', true) . '@example.com',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone' => '9876543212',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ]);

        $response->assertStatus(422);
    }

    public function test_register_candidate_rejects_duplicate_email(): void
    {
        $packageUuid = (string) Package::query()->where('code', 'PARICHAY_FREE')->value('uuid');
        $email = 'dup-' . uniqid('', true) . '@example.com';

        $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'A',
            'last_name' => 'Verma',
            'email' => $email,
            'gender' => 'male',
            'date_of_birth' => '1992-01-01',
            'phone' => '9876543213',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ])->assertStatus(201);

        $this->postJson('/api/v1/auth/register-candidate', [
            'first_name' => 'B',
            'last_name' => 'Verma',
            'email' => $email,
            'gender' => 'female',
            'date_of_birth' => '1993-01-01',
            'phone' => '9876543214',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'package_uuid' => $packageUuid,
        ])->assertStatus(422);
    }

    public function test_registration_options_excludes_inactive_packages(): void
    {
        $inactiveId = (int) Package::query()->where('code', 'RISHTA_PRO')->value('id');
        DB::table('packages')
            ->where('id', $inactiveId)
            ->update(['is_active' => false]);

        $uuids = collect($this->getJson('/api/v1/auth/registration')->json('data.packages'))
            ->pluck('uuid')
            ->all();

        $inactiveUuid = (string) Package::query()->where('id', $inactiveId)->value('uuid');
        $this->assertNotContains($inactiveUuid, $uuids);
    }
}
