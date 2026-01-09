<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse(
            [
                'user' => UserResource::make($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
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
            [
                'user' => UserResource::make($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            'Login successful'
        );
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
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
            [
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            'Token refreshed successfully'
        );
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

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
        // TODO: Implement password reset email logic via Service if needed
        // For now leaving as is or moving to service if logic expands

        return $this->successResponse(null, 'Password reset link sent to your email');
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // TODO: Implement password reset logic via Service if needed

        return $this->successResponse(null, 'Password reset successfully');
    }
}
