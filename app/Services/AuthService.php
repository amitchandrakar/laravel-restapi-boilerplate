<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserVerificationDocument;
use App\Support\CacheKeys;
use App\Support\SanctumAuthToken;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    private const LOGIN_GUARD = 'web';

    public function __construct(
        private readonly PackagePermissionService $packagePermissionService,
        private readonly LoginLockoutService $loginLockoutService,
        private readonly CouponService $couponService,
        private readonly CouponPricingService $couponPricingService
    ) {}

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
            $user->assignRole(Role::findByName('candidate', 'web'));
        }
        $this->attachDefaultPackageForRegistration($user->id);
        $this->packagePermissionService->syncCandidatePermissions($user);

        $token = SanctumAuthToken::issue($user);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Public registration UI: active packages and active surnames.
     *
     * @return array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>}
     */
    public function registrationOptions(): array
    {
        $ttl = max(60, (int) config('cache_strategy.registration_options_seconds', 600));

        /** @var array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>} $data */
        $data = Cache::remember(
            CacheKeys::registrationOptions(),
            $ttl,
            fn(): array => $this->buildRegistrationOptions()
        );

        return $this->decorateRegistrationOptionsForPayments($data);
    }

    public function forgetRegistrationOptionsCache(): void
    {
        Cache::forget(CacheKeys::registrationOptions());
    }

    /**
     * @return array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>}
     */
    private function buildRegistrationOptions(): array
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
            'packages' => array_values($packages),
            'surnames' => array_values($surnames),
        ];
    }

    /**
     * Register a candidate with profile fields and an explicit package (by UUID).
     *
     * @param  array<string, mixed>  $data
     *
     * @return array{user: User, token: string, payment: array<string, mixed>|null}
     */
    public function registerCandidate(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $packageUuid = (string) $data['package_uuid'];
            $couponCode =
                isset($data['coupon_code']) && is_string($data['coupon_code']) ? trim($data['coupon_code']) : null;
            unset($data['package_uuid'], $data['password_confirmation'], $data['coupon_code']);
            $candidateRoleId = $this->candidateRoleId();

            /** @var Package|null $package */
            $package = Package::query()->where('uuid', $packageUuid)->where('is_active', true)->first();

            if (!($package instanceof Package)) {
                throw ValidationException::withMessages([
                    'package_uuid' => ['The selected package is invalid.'],
                ]);
            }
            $packageId = (int) $package->id;

            if ($candidateRoleId !== null) {
                $data['role_id'] = $candidateRoleId;
            }

            /** @var User $user */
            $user = User::create($data);

            if ($candidateRoleId !== null) {
                $user->assignRole(Role::findByName('candidate', 'web'));
            }

            $payable = $this->resolveRegistrationPayableRupees($package, $couponCode);
            $appliedCoupon = $this->resolveAppliedCoupon($package, $couponCode);

            if ($payable <= 0) {
                $subscriptionId = $this->attachSubscriptionForRegistration($user->id, $packageId, 'active', 'system');

                if ($appliedCoupon instanceof Coupon) {
                    $this->couponService->redeem($appliedCoupon, (int) $user->id, $subscriptionId);
                }

                $this->packagePermissionService->syncCandidatePermissions($user);
                $token = SanctumAuthToken::issue($user);

                return ['user' => $user, 'token' => $token, 'payment' => null];
            }

            throw ValidationException::withMessages([
                'package_uuid' => [
                    'Online payment is not configured. Please contact support or choose another package.',
                ],
            ]);
        });
    }

    /**
     * POST /me/registration/checkout — activate complimentary packages or reject unpaid gateway checkout.
     *
     * @return array<string, mixed>
     */
    public function prepareRegistrationCheckout(User $user, Package $package, ?string $couponCode = null): array
    {
        $packageId = (int) $package->id;
        $payable = $this->resolveRegistrationPayableRupees($package, $couponCode, $user);
        $appliedCoupon = $this->resolveAppliedCoupon($package, $couponCode, $user);

        if ($payable <= 0) {
            $subscriptionId = $this->attachSubscriptionForRegistration($user->id, $packageId, 'active', 'system');

            if ($appliedCoupon instanceof Coupon) {
                $this->couponService->redeem($appliedCoupon, (int) $user->id, $subscriptionId);
            }

            $user->refresh();
            $this->packagePermissionService->syncCandidatePermissions($user);

            return [
                'skip_checkout' => true,
                'reason' => $appliedCoupon instanceof Coupon ? 'coupon_applied' : 'free_package',
            ];
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()->where('user_id', $user->id)->where('package_id', $packageId)->first();

        if ($subscription instanceof Subscription && $subscription->subscription_status === 'active') {
            return [
                'skip_checkout' => true,
                'reason' => 'already_subscribed',
            ];
        }

        throw ValidationException::withMessages([
            'package_uuid' => ['Online payment is not configured. Please contact support or choose another package.'],
        ]);
    }

    /**
     * GET /me/registration/status — onboarding gate for payment + KYC.
     *
     * @return array<string, mixed>
     */
    public function registrationStatusForMember(User $user, ?string $packageUuidQuery): array
    {
        $package = $this->resolveRegistrationPackageForStatus($user, $packageUuidQuery);
        $paymentBlock =
            $package instanceof Package
                ? $this->buildRegistrationPaymentStatusBlock($user, $package)
                : [
                    'resolved' => false,
                    'message' => 'Pass package_uuid query or complete a registration checkout to resolve payment state.',
                ];

        $aadhaarRaw = $user
            ->verificationDocuments()
            ->where('document_type', KycDocumentService::DOCUMENT_AADHAAR)
            ->first();
        $aadhaar = $aadhaarRaw instanceof UserVerificationDocument ? $aadhaarRaw : null;

        $kycStatus = $aadhaar === null ? 'not_submitted' : (string) $aadhaar->verification_status;

        $profileStatus = (string) ($user->profile_status ?? 'draft');

        $nextStep = $this->inferRegistrationNextStep($paymentBlock, $kycStatus, $profileStatus);

        return [
            'user_uuid' => (string) $user->uuid,
            'profile_status' => $profileStatus,
            'package' => $package instanceof Package
                    ? [
                        'uuid' => (string) $package->uuid,
                        'name' => (string) $package->name,
                        'registration_payable_rupees' => $package->registrationPayableAmountRupees(),
                    ]
                    : null,
            'payment' => $paymentBlock,
            'kyc' => [
                'status' => $kycStatus,
                'document_uuid' => $aadhaar !== null ? (string) $aadhaar->uuid : null,
                'submitted_at' => $aadhaar !== null && $aadhaar->submitted_at !== null
                        ? Carbon::parse($aadhaar->submitted_at)->toIso8601String()
                        : null,
                'rejection_reason' => $aadhaar !== null && in_array($kycStatus, ['rejected', 'resubmission_required'], true)
                        ? ($aadhaar->rejection_reason !== null && $aadhaar->rejection_reason !== ''
                            ? (string) $aadhaar->rejection_reason
                            : null)
                        : null,
            ],
            'next_step' => $nextStep,
        ];
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

        if ($user !== null) {
            $this->loginLockoutService->assertNotLocked($user);
        } elseif ($username !== '' && $this->loginLockoutService->isLockedForIdentifier($username)) {
            throw new HttpException(
                423,
                'Account is temporarily locked due to too many failed login attempts. Please try again later.'
            );
        }

        if ($user === null || !Hash::check($password, $user->getAuthPassword())) {
            if ($user !== null) {
                $this->loginLockoutService->recordFailedAttempt($user);
            } elseif ($username !== '') {
                $this->loginLockoutService->recordFailedAttemptForIdentifier($username);
            }

            throw new AuthenticationException('Invalid credentials');
        }

        $this->loginLockoutService->clear($user);

        if ((string) ($user->profile_status ?? '') === 'spam' || (string) ($user->status ?? 'active') === 'inactive') {
            throw new AuthenticationException('Your account has been deactivated. Please contact support.');
        }

        // API auth uses Sanctum personal access tokens only.
        // Sanctum checks the web guard first; a session user + TransientToken would bypass PAT
        // validation and keep the user "logged in" after the PAT is revoked on logout.

        $token = SanctumAuthToken::issue($user);

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

        $token = SanctumAuthToken::issue($user);

        return ['user' => $user, 'token' => $token];
    }

    private function candidateRoleId(): ?int
    {
        // Roles are seeded on the `web` guard. Do not use Auth::getDefaultDriver() /
        // config('auth.defaults.guard') here — auth:sanctum mutates that to `sanctum`.
        $roleId = Role::query()->where('name', 'candidate')->where('guard_name', 'web')->value('id');

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
     *
     * @return array<string, mixed>
     */
    private function mapRegisterPayload(array $data): array
    {
        unset($data['password_confirmation'], $data['package_uuid']);

        if (isset($data['name'])) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2, PREG_SPLIT_NO_EMPTY);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        if (array_key_exists('phone', $data)) {
            $phone = is_string($data['phone']) ? trim($data['phone']) : '';
            $data['phone'] = $phone !== '' ? $phone : null;
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

        $this->attachSubscriptionForRegistration($userId, $defaultPackageId, 'active', 'system');
    }

    /**
     * @return int Subscription primary key (0 if package invalid)
     */
    private function attachSubscriptionForRegistration(
        int $userId,
        int $packageId,
        string $subscriptionStatus = 'active',
        string $renewalSource = 'system'
    ): int {
        if ($packageId <= 0) {
            return 0;
        }

        $now = now();
        $existing = DB::table('subscriptions')->where('user_id', $userId)->where('package_id', $packageId)->first();

        $uuid = $existing !== null && isset($existing->uuid) ? (string) $existing->uuid : (string) Str::uuid();

        $endsAt = $this->registrationSubscriptionEndsAt($packageId, $now);

        $row = [
            'uuid' => $uuid,
            'subscription_status' => $subscriptionStatus,
            'started_at' => $now,
            'ends_at' => $endsAt,
            'auto_renew' => false,
            'renewal_source' => $renewalSource,
            'updated_at' => $now,
        ];

        if ($existing === null) {
            $row['created_at'] = $now;
            $id = DB::table('subscriptions')->insertGetId(
                array_merge($row, [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                ])
            );

            return $id;
        }

        DB::table('subscriptions')->where('id', $existing->id)->update($row);

        return (int) $existing->id;
    }

    private function registrationSubscriptionEndsAt(int $packageId, Carbon $now): Carbon
    {
        /** @var Package|null $package */
        $package = Package::query()->whereKey($packageId)->first();

        if ($package instanceof Package) {
            $durationUnit = (string) ($package->duration_unit ?? 'year');
            $durationValue = max(1, (int) ($package->getAttribute('duration_value') ?? 1));

            if ($durationUnit === 'month') {
                return $now->copy()->addMonths($durationValue);
            }

            return $now->copy()->addYears($durationValue);
        }

        return $now->copy()->addYear();
    }

    private function resolveRegistrationPayableRupees(
        Package $package,
        ?string $couponCode = null,
        ?User $user = null
    ): int {
        $coupon = $this->resolveAppliedCoupon($package, $couponCode, $user);

        return $this->couponPricingService->apply($package, $coupon);
    }

    private function resolveAppliedCoupon(Package $package, ?string $couponCode = null, ?User $user = null): ?Coupon
    {
        if ($couponCode !== null && trim($couponCode) !== '') {
            return $this->couponService->resolveEligibleCoupon($package, $couponCode, $user);
        }

        return $this->couponService->bestEligibleCouponForPackage($package, $user);
    }

    /**
     * @param  array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>}  $data
     *
     * @return array{packages: list<array<string, mixed>>, surnames: list<array{id: int, name: string}>}
     */
    private function decorateRegistrationOptionsForPayments(array $data): array
    {
        $data['packages'] = array_map(function (array $pkg): array {
            $packageId = isset($pkg['id']) ? (int) $pkg['id'] : 0;
            /** @var Package|null $packageModel */
            $packageModel = $packageId > 0 ? Package::query()->whereKey($packageId)->first() : null;

            if ($packageModel instanceof Package) {
                $coupon = $this->couponService->bestEligibleCouponForPackage($packageModel);
                $pkg['registrationPayableRupees'] = $this->couponPricingService->apply($packageModel, $coupon);
                $pkg['availableCoupons'] = [];
            } else {
                $discounted = $pkg['discountedPrice'] ?? null;
                $price = $pkg['price'] ?? 0;
                $pkg['registrationPayableRupees'] = max(0.0, (float) ($discounted !== null ? $discounted : $price));
                $pkg['availableCoupons'] = [];
            }

            return $pkg;
        }, $data['packages']);

        return $data;
    }

    private function resolveRegistrationPackageForStatus(User $user, ?string $packageUuidQuery): ?Package
    {
        if ($packageUuidQuery !== null && trim($packageUuidQuery) !== '') {
            /** @var Package|null $p */
            $p = Package::query()->where('uuid', $packageUuidQuery)->where('is_active', true)->first();

            return $p;
        }

        /** @var Subscription|null $pendingSub */
        $pendingSub = Subscription::query()
            ->where('user_id', $user->id)
            ->where('subscription_status', 'pending')
            ->orderByDesc('id')
            ->first();

        if ($pendingSub !== null) {
            /** @var Package|null $pkg */
            $pkg = Package::query()->whereKey((int) $pendingSub->package_id)->where('is_active', true)->first();

            return $pkg;
        }

        /** @var Subscription|null $activeSub */
        $activeSub = Subscription::query()
            ->where('user_id', $user->id)
            ->where('subscription_status', 'active')
            ->orderByDesc('id')
            ->first();

        if ($activeSub !== null) {
            /** @var Package|null $pkg */
            $pkg = Package::query()->whereKey((int) $activeSub->package_id)->where('is_active', true)->first();

            return $pkg;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRegistrationPaymentStatusBlock(User $user, Package $package): array
    {
        $packageId = $package->id;
        $payable = $package->registrationPayableAmountRupees();

        if ($payable <= 0) {
            return [
                'resolved' => true,
                'registration_payable_rupees' => $payable,
                'skip_checkout' => true,
                'subscription_status' => null,
                'payment_status' => null,
                'pending_payment_uuid' => null,
                'gateway_order_id' => null,
            ];
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()->where('user_id', $user->id)->where('package_id', $packageId)->first();

        if (!($subscription instanceof Subscription)) {
            return [
                'resolved' => true,
                'registration_payable_rupees' => $payable,
                'skip_checkout' => false,
                'subscription_status' => null,
                'payment_status' => null,
                'pending_payment_uuid' => null,
                'gateway_order_id' => null,
                'awaiting_checkout' => true,
            ];
        }

        /** @var Payment|null $latestPayment */
        $latestPayment = Payment::query()
            ->where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->orderByDesc('id')
            ->first();

        if ($subscription->subscription_status === 'active') {
            return [
                'resolved' => true,
                'registration_payable_rupees' => $payable,
                'skip_checkout' => true,
                'reason' => 'subscription_active',
                'subscription_status' => 'active',
                'payment_status' => $latestPayment !== null ? (string) $latestPayment->payment_status : null,
                'pending_payment_uuid' => null,
                'gateway_order_id' => $latestPayment !== null ? $latestPayment->gateway_order_id : null,
            ];
        }

        return [
            'resolved' => true,
            'registration_payable_rupees' => $payable,
            'skip_checkout' => false,
            'subscription_status' => (string) $subscription->subscription_status,
            'payment_status' => $latestPayment !== null ? (string) $latestPayment->payment_status : null,
            'pending_payment_uuid' => $latestPayment !== null ? (string) $latestPayment->uuid : null,
            'gateway_order_id' => $latestPayment !== null ? $latestPayment->gateway_order_id : null,
            'awaiting_checkout' => $latestPayment === null ||
                ($latestPayment->payment_status === 'pending' && $latestPayment->gateway_order_id === null),
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentBlock
     */
    private function inferRegistrationNextStep(array $paymentBlock, string $kycStatus, string $profileStatus): string
    {
        $payableResolved = (bool) ($paymentBlock['resolved'] ?? false);
        $subscriptionStatus = isset($paymentBlock['subscription_status'])
            ? (string) $paymentBlock['subscription_status']
            : '';

        if (
            !$payableResolved ||
            (!(bool) ($paymentBlock['skip_checkout'] ?? false) &&
                ($subscriptionStatus === 'pending' || $subscriptionStatus === ''))
        ) {
            return 'payment';
        }

        if (!in_array($kycStatus, ['approved'], true)) {
            if (in_array($kycStatus, ['pending'], true)) {
                return 'pending_review';
            }

            return 'verify_identity';
        }

        if ($profileStatus !== 'published') {
            return 'complete_profile';
        }

        return 'done';
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPublicRegistrationPackage(Package $package): array
    {
        $durationUnit = $package->duration_unit ?? 'year';
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
