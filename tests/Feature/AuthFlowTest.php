<?php

declare(strict_types=1);
use App\Events\ForgotPasswordRequestedEvent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PackageCatalogSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;

const REGISTER_PASSWORD = 'Password@flow1';
const NEW_PASSWORD_AFTER_RESET = 'Password@flow2';

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('covers registration, login, forgot password, and anonymous password reset', function (): void {
    $email = 'auth-flow-' . uniqid('', true) . '@example.com';

    $register = $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Auth Flow Test',
        'email' => $email,
        'password' => REGISTER_PASSWORD,
        'password_confirmation' => REGISTER_PASSWORD,
    ]);
    $register->assertStatus(201)->assertJsonPath('success', true)->assertJsonPath('data.token_type', 'Bearer');
    $registerToken = $register->json('data.token');
    expect($registerToken)->toBeString();
    expect(strlen($registerToken))->toBeGreaterThan(20);

    $registeredUser = User::query()->where('email', $email)->first();
    expect($registeredUser)->not->toBeNull();
    expect($registeredUser->hasRole('candidate'))->toBeTrue();
    $candidateRoleId = (int) Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');
    expect((int) $registeredUser->role_id)->toBe($candidateRoleId);

    $login = $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => REGISTER_PASSWORD,
    ]);
    $login->assertStatus(200)->assertJsonPath('success', true);
    expect((array) $login->json('data.permissions'))->toContain('admin.candidates.edit');
    $loginToken = $login->json('data.token');
    expect($loginToken)->toBeString();
    assertSessionTokenHashMatchesPlainToken($loginToken, $login->json('data.session_token_hash'));

    $capturedResetToken = null;
    Event::listen(ForgotPasswordRequestedEvent::class, function (ForgotPasswordRequestedEvent $e) use (
        &$capturedResetToken
    ): void {
        $capturedResetToken = $e->resetHash;
    });

    $forgot = $this->postJson('/api/v1/app/auth/forgot-password', [
        'email' => $email,
    ]);
    $forgot->assertStatus(200)->assertJsonPath('success', true);
    expect($capturedResetToken)->toBeString();
    expect(strlen((string) $capturedResetToken))->toBeGreaterThan(20);
    expect($capturedResetToken)->not->toBeNull();

    /** @var string $resetToken */
    $resetToken = $capturedResetToken;

    $reset = $this->postJson('/api/v1/app/auth/reset-password', [
        'email' => $email,
        'token' => $resetToken,
        'password' => NEW_PASSWORD_AFTER_RESET,
        'password_confirmation' => NEW_PASSWORD_AFTER_RESET,
    ]);
    $reset->assertStatus(200)->assertJsonPath('success', true);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => REGISTER_PASSWORD,
    ])->assertStatus(401);

    $final = $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => NEW_PASSWORD_AFTER_RESET,
    ]);
    $final->assertStatus(200)->assertJsonPath('success', true);
    $finalToken = $final->json('data.token');
    expect($finalToken)->toBeString();
    assertSessionTokenHashMatchesPlainToken($finalToken, $final->json('data.session_token_hash'));
});

it('returns validation errors for forgot-password requests with unknown emails', function () {
    $this->postJson('/api/v1/app/auth/forgot-password', [
        'email' => 'nobody-' . uniqid('', true) . '@example.com',
    ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('subscribes new users to the default registration package when configured', function (): void {
    $this->seed(PackageCatalogSeeder::class);

    $email = 'auth-default-package-' . uniqid('', true) . '@example.com';
    $password = 'Password@default1';

    $this->postJson('/api/v1/app/auth/register', [
        'name' => 'Default Package User',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertStatus(201);

    $userId = (int) User::query()->where('email', $email)->value('id');
    $defaultPackageId = (int) DB::table('packages')->where('is_default_registration', true)->value('id');

    expect($defaultPackageId)->toBeGreaterThan(0);
    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $userId,
        'package_id' => $defaultPackageId,
        'subscription_status' => 'active',
    ]);
});

it('accepts discrete first and last names when the legacy name field is omitted', function () {
    $email = 'auth-split-name-' . uniqid('', true) . '@example.com';
    $password = 'Password@split1';

    $this->postJson('/api/v1/app/auth/register', [
        'first_name' => 'Split',
        'last_name' => 'NameUser',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $user = User::query()->where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->first_name)->toBe('Split');
    expect($user->last_name)->toBe('NameUser');
});

it('rotates tokens on refresh and revokes the refreshed token after logout', function (): void {
    [$email, $password, $token] = registerAndLogin();

    $this->withToken($token)
        ->getJson('/api/v1/app/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $email);

    $refresh = $this->withToken($token)->postJson('/api/v1/app/auth/refresh');
    $refresh->assertStatus(200)->assertJsonPath('success', true);
    $newToken = (string) $refresh->json('data.token');
    expect($newToken)->not->toBe($token);

    $this->withToken($newToken)
        ->postJson('/api/v1/app/auth/logout')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(PersonalAccessToken::findToken($newToken))->toBeNull(
        'Sanctum token should be removed from storage after logout'
    );

    $this->withToken($newToken)->getJson('/api/v1/app/auth/me')->assertStatus(401);
});

it('authenticates candidates using either phone or email as the username', function () {
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

    $this->postJson('/api/v1/app/auth/login', [
        'username' => '9876500011',
        'password' => 'Password@phone1',
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $user->email,
        'password' => 'Password@phone1',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.user.id', $user->id);
});

it('lets authenticated users reset their password with a valid current password', function (): void {
    [$email, $password, $token] = registerAndLogin();

    $newPassword = 'Password@resetViaApi1';

    $this->withToken($token)
        ->postJson('/api/v1/app/auth/reset-password', [
            'current_password' => $password,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => $password,
    ])->assertStatus(401);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => $newPassword,
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('rejects authenticated password resets when the current password is incorrect', function (): void {
    [, $password, $token] = registerAndLogin();

    $this->withToken($token)
        ->postJson('/api/v1/app/auth/reset-password', [
            'current_password' => $password . 'wrong',
            'password' => 'Password@newValid1',
            'password_confirmation' => 'Password@newValid1',
        ])
        ->assertStatus(403);
});

it('rejects reset-password mutations that lack a valid bearer token', function () {
    $this->withToken('invalid-token-that-does-not-exist')
        ->postJson('/api/v1/app/auth/reset-password', [
            'current_password' => 'x',
            'password' => 'Password@newValid2',
            'password_confirmation' => 'Password@newValid2',
        ])
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('supports updating profile metadata and rotating the password in sequence', function (): void {
    [$email, $password, $token] = registerAndLogin();

    $this->withToken($token)
        ->patchJson('/api/v1/app/auth/profile', [
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
        ->postJson('/api/v1/app/auth/change-password', [
            'current_password' => $password,
            'password' => 'Password@changed1',
            'password_confirmation' => 'Password@changed1',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => $password,
    ])->assertStatus(401);

    $this->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => 'Password@changed1',
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});
function assertSessionTokenHashMatchesPlainToken(string $plainToken, mixed $sessionTokenHash): void
{
    expect($sessionTokenHash)->toBeString();
    expect(strlen($sessionTokenHash))->toBe(64);
    $parts = explode('|', $plainToken, 2);
    $tokenValue = $parts[1] ?? $parts[0];

    expect($sessionTokenHash)->toBe(hash('sha256', $tokenValue));
}
/**
 * @return array{0: string, 1: string, 2: string}
 */
function registerAndLogin(): array
{
    $email = 'auth-user-' . uniqid('', true) . '@example.com';
    $password = 'Password@seed1';

    test()
        ->postJson('/api/v1/app/auth/register', [
            'name' => 'Auth Test User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertStatus(201);

    $login = test()->postJson('/api/v1/app/auth/login', [
        'username' => $email,
        'password' => $password,
    ]);
    $login->assertStatus(200)->assertJsonPath('success', true);

    return [$email, $password, (string) $login->json('data.token')];
}
