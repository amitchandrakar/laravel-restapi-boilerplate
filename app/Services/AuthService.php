<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        /** @var User $user */
        $user = User::create($data);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Login user.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new AuthenticationException('Invalid credentials');
        }

        /** @var User $user */
        $user = Auth::user();

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Logout user.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Refresh token.
     *
     * @return array{user: User, token: string}
     */
    public function refresh(User $user): array
    {
        $user->currentAccessToken()->delete();

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
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all tokens except current one
        $user
            ->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();
    }
}
