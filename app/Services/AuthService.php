<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
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
        $candidateRoleId = $this->candidateRoleId();
        if ($candidateRoleId !== null) {
            $data['role_id'] = $candidateRoleId;
        }

        /** @var User $user */
        $user = User::create($data);

        if ($candidateRoleId !== null) {
            $user->assignRole('candidate');
        }
        $this->attachDefaultPackageForRegistration($user->id);
        $this->packagePermissionService->syncCandidatePermissions($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Public registration UI: active packages and active surnames.
     *
     * @return array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>}
     */
    public function registrationOptions(): array
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn(Package $package): array => $this->mapPublicRegistrationPackage($package))
            ->values()
            ->all();

        $surnames = DB::table('surnames')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(
                static fn($row): array => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                ]
            )
            ->values()
            ->all();

        return [
            'packages' => $packages,
            'surnames' => $surnames,
        ];
    }

    /**
     * Register a candidate with profile fields and an explicit package (by UUID).
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function registerCandidate(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $packageUuid = (string) $data['package_uuid'];
            unset($data['package_uuid'], $data['password_confirmation']);
            $candidateRoleId = $this->candidateRoleId();

            $packageId = (int) Package::query()->where('uuid', $packageUuid)->where('is_active', true)->value('id');
            if ($packageId === 0) {
                throw ValidationException::withMessages([
                    'package_uuid' => ['The selected package is invalid.'],
                ]);
            }
            if ($candidateRoleId !== null) {
                $data['role_id'] = $candidateRoleId;
            }

            /** @var User $user */
            $user = User::create($data);

            if ($candidateRoleId !== null) {
                $user->assignRole('candidate');
            }

            $this->attachSubscriptionForRegistration($user->id, $packageId);
            $this->packagePermissionService->syncCandidatePermissions($user);

            $token = $user->createToken('auth-token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }

    /**
     * Login user.
     *
     * `username` may be the user's email or phone as stored on their record.
     *
     * @return array{user: User, token: string, permissions: array<int, string>}
     */
    public function login(array $credentials): array
    {
        $username = trim((string) ($credentials['username'] ?? ''));
        $password = (string) ($credentials['password'] ?? '');

        $user = User::query()
            ->where(static function ($query) use ($username): void {
                $query->where('email', $username)->orWhere('phone', $username);
            })
            ->first();

        if ($user === null || !Hash::check($password, $user->getAuthPassword())) {
            throw new AuthenticationException('Invalid credentials');
        }

        // API auth uses Sanctum personal access tokens only. Avoid web-guard session login here:
        // Sanctum checks the web guard first; a session user + TransientToken would bypass PAT
        // validation and keep the user "logged in" after the PAT is revoked on logout.

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    /**
     * Log the user out: revoke the current Sanctum access token (this device), then clear the web guard session.
     *
     * When both a session and a Bearer token are present, Sanctum resolves the session first and sets a
     * {@see TransientToken} on the user, so we revoke using the raw Bearer value when provided.
     *
     * @param  string|null  $plainTextBearerToken  Raw `Authorization: Bearer` value (e.g. `{id}|{secret}`).
     */
    public function logout(User $user, ?string $plainTextBearerToken = null): void
    {
        if ($plainTextBearerToken !== null && $plainTextBearerToken !== '') {
            $accessToken = PersonalAccessToken::findToken($plainTextBearerToken);
            if (
                $accessToken !== null &&
                (int) $accessToken->tokenable_id === (int) $user->id &&
                $accessToken->tokenable_type === $user->getMorphClass()
            ) {
                $accessToken->delete();
            }
        } else {
            /** @var mixed $current */
            $current = $user->currentAccessToken();
            if (is_object($current) && method_exists($current, 'delete')) {
                $current->delete();
            }
        }

        Auth::guard(self::LOGIN_GUARD)->logout();
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

    private function candidateRoleId(): ?int
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $roleId = Role::query()->where('name', 'candidate')->where('guard_name', $guard)->value('id');

        return $roleId !== null ? (int) $roleId : null;
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
            ->whereNull('deleted_at')
            ->value('id');
        if ($defaultPackageId === 0) {
            return;
        }

        $this->attachSubscriptionForRegistration($userId, $defaultPackageId);
    }

    private function attachSubscriptionForRegistration(int $userId, int $packageId): void
    {
        if ($packageId <= 0) {
            return;
        }

        $now = now();
        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'package_id' => $packageId],
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

    /**
     * @return array<string, mixed>
     */
    private function mapPublicRegistrationPackage(Package $package): array
    {
        $durationUnit = (string) ($package->duration_unit ?? 'year');
        $durationDays = $durationUnit === 'year' ? 365 : 30;
        $monthlyPrice = (float) ($package->monthly_price ?? 0);
        $yearlyPrice = (float) ($package->yearly_price ?? ($package->price ?? 0));
        $displayPrice = $durationUnit === 'year' ? $yearlyPrice : $monthlyPrice;
        $pricePerDay = round($displayPrice / $durationDays, 2);
        $durationValue = (int) ($package->getAttribute('duration_value') ?? 1);

        $rawPrice = $package->getRawOriginal('price');
        $rawDiscounted = $package->getRawOriginal('discounted_price');

        return [
            'id' => $package->id,
            'uuid' => $package->uuid,
            'name' => $package->name,
            'code' => $package->code,
            'description' => $package->description,
            'durationUnit' => $durationUnit,
            'durationValue' => $durationValue,
            'durationDays' => $durationDays,
            'pricePerDay' => $pricePerDay,
            'monthlyPrice' => $monthlyPrice,
            'yearlyPrice' => $yearlyPrice,
            'price' => $rawPrice === null ? null : (float) $rawPrice,
            'discountedPrice' => $rawDiscounted === null ? null : (float) $rawDiscounted,
            'currency' => $package->currency,
            'isActive' => (bool) $package->is_active,
            'isDefaultRegistration' => (bool) ($package->is_default_registration ?? false),
            'isPopular' => (bool) ($package->is_popular ?? false),
            'sortOrder' => (int) ($package->sort_order ?? 0),
        ];
    }
}
