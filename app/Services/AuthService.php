<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    private const LOGIN_GUARD = 'web';

    public function __construct(private readonly PackagePermissionService $packagePermissionService) {}

    /**
     * Register a new user.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $data = $this->mapRegisterPayload($data);

        /** @var User $user */
        $user = User::create($data);

        $guard = (string) config('auth.defaults.guard', 'web');
        if (Role::query()->where('name', 'candidate')->where('guard_name', $guard)->exists()) {
            $user->assignRole('candidate');
        }
        $this->attachDefaultPackageForRegistration($user->id);
        $this->packagePermissionService->syncCandidatePermissions($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Login user.
     *
     * @return array{user: User, token: string, permissions: array<int, string>}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        if (!Auth::guard(self::LOGIN_GUARD)->attempt($credentials)) {
            throw new AuthenticationException('Invalid credentials');
        }

        /** @var User $user */
        $user = Auth::guard(self::LOGIN_GUARD)->user();

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    /**
     * Logout user.
     */
    public function logout(User $user): void
    {
        /** @var mixed $currentToken */
        $currentToken = $user->currentAccessToken();
        if (is_object($currentToken) && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }
    }

    /**
     * Refresh token.
     *
     * @return array{user: User, token: string}
     */
    public function refresh(User $user): array
    {
        /** @var mixed $currentToken */
        $currentToken = $user->currentAccessToken();
        if (is_object($currentToken) && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * Change password.
     *
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new HttpException(403, 'Current password is incorrect');
        }

        $user->update([
            'password' => $newPassword,
        ]);

        // Revoke all tokens except current one
        /** @var mixed $currentToken */
        $currentToken = $user->currentAccessToken();
        $currentTokenId = is_object($currentToken) && property_exists($currentToken, 'id') ? $currentToken->id : null;
        if (is_numeric($currentTokenId)) {
            $user->tokens()->where('id', '!=', (int) $currentTokenId)->delete();

            return;
        }

        $user->tokens()->delete();
    }

    /**
     * Map validated register payload (legacy `name`) to `users` columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapRegisterPayload(array $data): array
    {
        unset($data['password_confirmation']);

        if (isset($data['name'])) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        return $data;
    }

    private function attachDefaultPackageForRegistration(int $userId): void
    {
        $defaultPackageId = (int) DB::table('packages')
            ->where('is_active', true)
            ->where('is_default_registration', true)
            ->value('id');
        if ($defaultPackageId === 0) {
            return;
        }

        $now = now();
        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $defaultPackageId],
            [
                'uuid' => (string) Str::uuid(),
                'subscription_status' => 'active',
                'started_at' => $now,
                'ends_at' => $now->copy()->addYear(),
                'auto_renew' => false,
                'renewal_source' => 'system',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}
