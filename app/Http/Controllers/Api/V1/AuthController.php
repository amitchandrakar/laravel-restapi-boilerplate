<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\ForgotPasswordRequestedEvent;
use App\Http\Requests\Api\V1\CandidateRegistrationOptionsRequest;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterCandidateRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\AuthLoginResource;
use App\Http\Resources\Api\V1\TokenResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Jobs\EndUserSessionJob;
use App\Jobs\LogAuditJob;
use App\Jobs\LogUserActivityJob;
use App\Jobs\StartUserSessionJob;
use App\Jobs\UpsertUserDeviceLogJob;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    /**
     * Public data for candidate registration (packages + surnames).
     */
    public function registrationOptions(CandidateRegistrationOptionsRequest $request): JsonResponse
    {
        return $this->successResponse(
            $this->authService->registrationOptions(),
            'Registration options fetched successfully'
        );
    }

    /**
     * Register a candidate with profile fields and selected package.
     */
    public function registerCandidate(RegisterCandidateRequest $request): JsonResponse
    {
        $result = $this->authService->registerCandidate($request->validated());
        /** @var User $user */
        $user = $result['user'];
        $meta = $this->requestMeta($request);

        LogAuditJob::dispatch(
            $user->id,
            'users',
            (int) $user->id,
            'register_candidate',
            null,
            ['email' => $user->email, 'package_uuid' => $request->input('package_uuid')],
            $meta['ip'],
            $meta['ua']
        );
        LogUserActivityJob::dispatch(
            $user->id,
            'auth.register_candidate',
            'api_v1_auth',
            ['email' => $user->email],
            $meta['ip']
        );
        UpsertUserDeviceLogJob::dispatch($user->id, $meta['device_id'], 'web', $meta['device_name'], $meta['os_name']);

        $user->refresh();

        return $this->createdResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($user),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ]),
            'Candidate registered successfully'
        );
    }

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
        /** @var User $user */
        $user = $result['user'];
        $meta = $this->requestMeta($request);

        LogAuditJob::dispatch(
            $user->id,
            'users',
            (int) $user->id,
            'register',
            null,
            ['email' => $user->email],
            $meta['ip'],
            $meta['ua']
        );
        LogUserActivityJob::dispatch($user->id, 'auth.register', 'api_v1_auth', ['email' => $user->email], $meta['ip']);
        UpsertUserDeviceLogJob::dispatch($user->id, $meta['device_id'], 'web', $meta['device_name'], $meta['os_name']);

        return $this->createdResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($user),
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
        /** @var User $user */
        $user = $result['user'];
        $meta = $this->requestMeta($request);
        $tokenHash = $this->hashAccessToken((string) $result['token']);

        LogUserActivityJob::dispatch($user->id, 'auth.login', 'api_v1_auth', ['email' => $user->email], $meta['ip']);
        UpsertUserDeviceLogJob::dispatch($user->id, $meta['device_id'], 'web', $meta['device_name'], $meta['os_name']);
        StartUserSessionJob::dispatch($user->id, $tokenHash, null, $meta['ip'], $meta['ua'], $meta['device_id']);

        return $this->successResponse(
            AuthLoginResource::make([
                'user' => UserResource::make($user),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'permissions' => $result['permissions'],
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
        $meta = $this->requestMeta($request);
        $tokenHash = $this->hashAccessToken((string) ($request->bearerToken() ?? ''));
        $userId = (int) $request->user()->id;
        $this->authService->logout($request->user());
        EndUserSessionJob::dispatch($userId, $tokenHash);
        LogUserActivityJob::dispatch($userId, 'auth.logout', 'api_v1_auth', null, $meta['ip']);

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
            $mapped['first_name'] = $validated['firstName'];
        }
        if (array_key_exists('lastName', $validated)) {
            $mapped['last_name'] = $validated['lastName'];
        }
        if (array_key_exists('email', $validated)) {
            $mapped['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $mapped['phone'] = $validated['phone'];
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
            $plainToken = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );
            ForgotPasswordRequestedEvent::dispatch($user, $plainToken);
        }

        return $this->successResponse(null, 'Password reset link sent to your email');
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = (string) $request->input('email');
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$user || !$row || !Hash::check((string) $request->input('token'), (string) $row->token)) {
            return $this->errorResponse('Invalid or expired reset token', 400);
        }

        $expiresMinutes = (int) config('auth.passwords.users.expire', 60);
        if ($row->created_at !== null && Carbon::parse($row->created_at)->addMinutes($expiresMinutes)->isPast()) {
            return $this->errorResponse('Invalid or expired reset token', 400);
        }

        $user->password = (string) $request->input('password');
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $user->tokens()->delete();

        return $this->successResponse(null, 'Password reset successfully');
    }

    /**
     * @return array{ip: ?string, ua: ?string, device_id: string, device_name: string, os_name: string}
     */
    private function requestMeta(Request $request): array
    {
        $ua = (string) ($request->userAgent() ?? 'unknown');

        return [
            'ip' => $request->ip(),
            'ua' => $ua,
            'device_id' => hash('sha256', $ua . '|' . (string) $request->ip()),
            'device_name' => str($ua)->limit(120, '')->toString(),
            'os_name' => str($ua)->contains('Windows')
                ? 'Windows'
                : (str($ua)->contains('Macintosh')
                    ? 'macOS'
                    : 'Web'),
        ];
    }

    private function hashAccessToken(string $plainTextToken): string
    {
        if ($plainTextToken === '') {
            return '';
        }
        $parts = explode('|', $plainTextToken, 2);
        $tokenValue = $parts[1] ?? $parts[0];

        return hash('sha256', $tokenValue);
    }
}
