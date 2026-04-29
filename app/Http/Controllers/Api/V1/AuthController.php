<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\ForgotPasswordRequestedEvent;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\AuthLoginResource;
use App\Http\Resources\Api\V1\TokenResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    /**
     * If the request includes user_id or userId, ensure it matches the authenticated user.
     * Returns a 403 JsonResponse when it does not match, or null when no check or match.
     */
    private function validateRequestedUserId(Request $request): ?JsonResponse
    {
        $requested = $request->input('user_id') ?? $request->input('userId');
        if ($requested === null) {
            return null;
        }
        $user = $request->user();
        $matches = is_numeric($requested)
            ? (int) $requested === (int) $user->id
            : (string) $requested === (string) $user->uuid;
        if (!$matches) {
            return $this->errorResponse('Forbidden', 403);
        }

        return null;
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ]),
            'User registered successfully'
        );
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->only('email', 'password'));

        return $this->successResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ]),
            'Login successful'
        );
    }

    /**
     * Get authenticated user (profile).
     * Optional user_id/userId in query or body must match the authenticated user or 403 is returned.
     */
    public function me(Request $request): JsonResponse
    {
        $forbidden = $this->validateRequestedUserId($request);
        if ($forbidden !== null) {
            return $forbidden;
        }

        return $this->successResponse(UserResource::make($request->user()), 'User retrieved successfully');
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        return $this->successResponse(
            TokenResource::make([
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ]),
            'Token refreshed successfully'
        );
    }

    /**
     * Update user profile.
     * Optional user_id/userId in body must match the authenticated user or 403 is returned.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $forbidden = $this->validateRequestedUserId($request);
        if ($forbidden !== null) {
            return $forbidden;
        }

        $validated = $request->validated();

        // Map API payload keys (camelCase) to User model columns.
        $mapped = [];
        if (array_key_exists('firstName', $validated)) {
            $mapped['fname'] = $validated['firstName'];
        }
        if (array_key_exists('lastName', $validated)) {
            $mapped['lname'] = $validated['lastName'];
        }
        if (array_key_exists('email', $validated)) {
            $mapped['email'] = $validated['email'];
        }
        if (array_key_exists('secondaryEmail', $validated)) {
            $mapped['secondary_email'] = $validated['secondaryEmail'];
        }
        if (array_key_exists('phone', $validated)) {
            $mapped['phone'] = $validated['phone'];
        }
        if (array_key_exists('secondaryPhone', $validated)) {
            $mapped['secondary_phone'] = $validated['secondaryPhone'];
        }
        if (array_key_exists('company', $validated)) {
            $mapped['company'] = $validated['company'];
        }
        if (array_key_exists('address', $validated)) {
            $mapped['addr'] = $validated['address'];
        }
        if (array_key_exists('address2', $validated)) {
            $mapped['addr2'] = $validated['address2'];
        }
        if (array_key_exists('city', $validated)) {
            $mapped['city'] = $validated['city'];
        }
        if (array_key_exists('state', $validated)) {
            $mapped['state'] = $validated['state'];
        }
        if (array_key_exists('zip', $validated)) {
            $mapped['zip'] = $validated['zip'];
        }

        $user = $this->authService->updateProfile($request->user(), $mapped);

        return $this->successResponse(UserResource::make($user), 'Profile updated successfully');
    }

    /**
     * Change user password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->user(), $request->current_password, $request->password);

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * Forgot password
     */
    /**
     * Forgot password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            $token = Str::random(48);
            $user->update([
                'forgot_password_link' => $token,
                'forgot_password_link_valid' => 1,
            ]);
            ForgotPasswordRequestedEvent::dispatch($user, $token);
        }

        return $this->successResponse(null, 'Password reset link sent to your email');
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->input('email'))->first();
        if (!$user || !$user->forgot_password_link_valid || $user->forgot_password_link !== $request->input('token')) {
            return $this->errorResponse('Invalid or expired reset token', 400);
        }

        $user->update([
            'password' => Hash::make((string) $request->input('password')),
            'forgot_password_link' => '',
            'forgot_password_link_valid' => 0,
        ]);
        $user->tokens()->delete();

        return $this->successResponse(null, 'Password reset successfully');
    }
}
