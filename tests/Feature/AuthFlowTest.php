<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ForgotPasswordRequestedEvent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;
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
        $candidateRoleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');
        $this->assertSame($candidateRoleId, (int) $registeredUser->role_id);

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => self::REGISTER_PASSWORD,
        ]);
        $login->assertStatus(200)->assertJsonPath('success', true);
        $this->assertContains('admin.candidates.edit', (array) $login->json('data.permissions'));
        $loginToken = $login->json('data.token');
        $this->assertIsString($loginToken);
        $this->assertSessionTokenHashMatchesPlainToken($loginToken, $login->json('data.session_token_hash'));

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
            'username' => $email,
            'password' => self::REGISTER_PASSWORD,
        ])->assertStatus(401);

        $final = $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => self::NEW_PASSWORD_AFTER_RESET,
        ]);
        $final->assertStatus(200)->assertJsonPath('success', true);
        $finalToken = $final->json('data.token');
        $this->assertIsString($finalToken);
        $this->assertSessionTokenHashMatchesPlainToken($finalToken, $final->json('data.session_token_hash'));
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

        $this->assertNull(
            PersonalAccessToken::findToken($newToken),
            'Sanctum token should be removed from storage after logout'
        );

        $this->withToken($newToken)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_login_accepts_phone_number(): void
    {
        $this->seed(RbacSeeder::class);

        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Phone',
            'last_name' => 'Login',
            'email' => 'phone-login-' . uniqid('', true) . '@example.com',
            'phone' => '9876500011',
            'password' => 'Password@phone1',
            'status' => 'active',
        ]);
        $user->assignRole('candidate');

        $this->postJson('/api/v1/auth/login', [
            'username' => '9876500011',
            'password' => 'Password@phone1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id);

        $this->postJson('/api/v1/auth/login', [
            'username' => $user->email,
            'password' => 'Password@phone1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_reset_password_while_logged_in_with_current_password(): void
    {
        $this->seed(RbacSeeder::class);
        [$email, $password, $token] = $this->registerAndLogin();

        $newPassword = 'Password@resetViaApi1';

        $this->withToken($token)
            ->postJson('/api/v1/auth/reset-password', [
                'current_password' => $password,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => $password,
        ])->assertStatus(401);

        $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => $newPassword,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_reset_password_while_logged_in_rejects_wrong_current_password(): void
    {
        $this->seed(RbacSeeder::class);
        [, $password, $token] = $this->registerAndLogin();

        $this->withToken($token)
            ->postJson('/api/v1/auth/reset-password', [
                'current_password' => $password . 'wrong',
                'password' => 'Password@newValid1',
                'password_confirmation' => 'Password@newValid1',
            ])
            ->assertStatus(403);
    }

    public function test_reset_password_rejects_invalid_bearer_token(): void
    {
        $this->seed(RbacSeeder::class);

        $this->withToken('invalid-token-that-does-not-exist')
            ->postJson('/api/v1/auth/reset-password', [
                'current_password' => 'x',
                'password' => 'Password@newValid2',
                'password_confirmation' => 'Password@newValid2',
            ])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
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
            'username' => $email,
            'password' => $password,
        ])->assertStatus(401);

        $this->postJson('/api/v1/auth/login', [
            'username' => $email,
            'password' => 'Password@changed1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    private function assertSessionTokenHashMatchesPlainToken(string $plainToken, mixed $sessionTokenHash): void
    {
        $this->assertIsString($sessionTokenHash);
        $this->assertSame(64, strlen($sessionTokenHash));
        $parts = explode('|', $plainToken, 2);
        $tokenValue = $parts[1] ?? $parts[0];

        $this->assertSame(hash('sha256', $tokenValue), $sessionTokenHash);
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
            'username' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200)->assertJsonPath('success', true);

        return [$email, $password, (string) $login->json('data.token')];
    }
}
