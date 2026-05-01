<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ForgotPasswordRequestedEvent;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER_PASSWORD = 'Password@flow1';

    private const NEW_PASSWORD_AFTER_RESET = 'Password@flow2';

    public function test_register_login_forgot_password_and_reset_password_flow(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'auth-flow-' . uniqid('', true) . '@example.com';

        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Auth Flow Test',
            'email' => $email,
            'password' => self::REGISTER_PASSWORD,
            'password_confirmation' => self::REGISTER_PASSWORD,
        ]);
        $register->assertStatus(201)->assertJsonPath('success', true)->assertJsonPath('data.token_type', 'Bearer');
        $registerToken = $register->json('data.token');
        $this->assertIsString($registerToken);
        $this->assertGreaterThan(20, strlen($registerToken));

        $registeredUser = User::query()->where('email', $email)->first();
        $this->assertNotNull($registeredUser);
        $this->assertTrue($registeredUser->hasRole('candidate'));

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => self::REGISTER_PASSWORD,
        ]);
        $login->assertStatus(200)->assertJsonPath('success', true)->assertJsonPath('data.permissions', []);
        $loginToken = $login->json('data.token');
        $this->assertIsString($loginToken);

        $capturedResetToken = null;
        Event::listen(ForgotPasswordRequestedEvent::class, function (ForgotPasswordRequestedEvent $e) use (
            &$capturedResetToken
        ): void {
            $capturedResetToken = $e->resetHash;
        });

        $forgot = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $forgot->assertStatus(200)->assertJsonPath('success', true);
        $this->assertIsString($capturedResetToken);
        $this->assertGreaterThan(20, strlen((string) $capturedResetToken));
        $this->assertNotNull($capturedResetToken);
        /** @var string $resetToken */
        $resetToken = $capturedResetToken;

        $reset = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'token' => $resetToken,
            'password' => self::NEW_PASSWORD_AFTER_RESET,
            'password_confirmation' => self::NEW_PASSWORD_AFTER_RESET,
        ]);
        $reset->assertStatus(200)->assertJsonPath('success', true);

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => self::REGISTER_PASSWORD,
        ])->assertStatus(401);

        $final = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => self::NEW_PASSWORD_AFTER_RESET,
        ]);
        $final->assertStatus(200)->assertJsonPath('success', true);
        $this->assertIsString($final->json('data.token'));
    }

    public function test_forgot_password_validates_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody-' . uniqid('', true) . '@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_register_assigns_default_package_subscription_when_available(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PackageCatalogSeeder::class);

        $email = 'auth-default-package-' . uniqid('', true) . '@example.com';
        $password = 'Password@default1';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Default Package User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $userId = (int) User::query()->where('email', $email)->value('id');
        $defaultPackageId = (int) DB::table('packages')->where('is_default_registration', true)->value('id');

        $this->assertGreaterThan(0, $defaultPackageId);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $userId,
            'package_id' => $defaultPackageId,
            'subscription_status' => 'active',
        ]);
    }

    public function test_register_accepts_first_name_and_last_name_without_name_field(): void
    {
        $this->seed(RbacSeeder::class);

        $email = 'auth-split-name-' . uniqid('', true) . '@example.com';
        $password = 'Password@split1';

        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Split',
            'last_name' => 'NameUser',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Split', $user->first_name);
        $this->assertSame('NameUser', $user->last_name);
    }

    public function test_me_refresh_and_logout_flow(): void
    {
        $this->seed(RbacSeeder::class);
        [$email, $password, $token] = $this->registerAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $email);

        $refresh = $this->withToken($token)->postJson('/api/v1/auth/refresh');
        $refresh->assertStatus(200)->assertJsonPath('success', true);
        $newToken = (string) $refresh->json('data.token');
        $this->assertNotSame($token, $newToken);

        $this->withToken($newToken)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_update_profile_and_change_password_flow(): void
    {
        $this->seed(RbacSeeder::class);
        [$email, $password, $token] = $this->registerAndLogin();

        $this->withToken($token)
            ->patchJson('/api/v1/auth/profile', [
                'firstName' => 'UpdatedFirst',
                'lastName' => 'UpdatedLast',
                'phone' => '9990001111',
                'userId' => User::query()->where('email', $email)->value('id'),
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.firstName', 'UpdatedFirst')
            ->assertJsonPath('data.lastName', 'UpdatedLast')
            ->assertJsonPath('data.phone', '9990001111');

        $this->withToken($token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => $password,
                'password' => 'Password@changed1',
                'password_confirmation' => 'Password@changed1',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertStatus(401);

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password@changed1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function registerAndLogin(): array
    {
        $email = 'auth-user-' . uniqid('', true) . '@example.com';
        $password = 'Password@seed1';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Auth Test User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(201);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200)->assertJsonPath('success', true);

        return [$email, $password, (string) $login->json('data.token')];
    }
}
