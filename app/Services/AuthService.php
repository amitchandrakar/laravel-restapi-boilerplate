<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserVerificationDocument;
use App\Services\Payment\RegistrationPaymentService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
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

    public function __construct(
        private readonly PackagePermissionService $packagePermissionService,
        private readonly RegistrationPaymentService $registrationPaymentService
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
     * @return array{user: User, token: string, payment: array<string, mixed>|null}
     */
    public function registerCandidate(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $packageUuid = (string) $data['package_uuid'];
            unset($data['package_uuid'], $data['password_confirmation']);
            $candidateRoleId = $this->candidateRoleId();

            /** @var Package|null $package */
            $package = Package::query()->where('uuid', $packageUuid)->where('is_active', true)->first();
            if (!$package instanceof Package) {
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
                $user->assignRole('candidate');
            }

            $payable = $package->registrationPayableAmountRupees();
            if ($payable <= 0) {
                $this->attachSubscriptionForRegistration($user->id, $packageId, 'active', 'system');
                $this->packagePermissionService->syncCandidatePermissions($user);
                $token = $user->createToken('auth-token')->plainTextToken;

                return ['user' => $user, 'token' => $token, 'payment' => null];
            }

            $subscriptionId = $this->attachSubscriptionForRegistration($user->id, $packageId, 'pending', 'gateway');
            $paymentMeta = $this->registrationPaymentService->createOrderForRegistration($user, $package, $subscriptionId);
            $token = $user->createToken('auth-token')->plainTextToken;

            return ['user' => $user, 'token' => $token, 'payment' => $paymentMeta];
        });
    }

    /**
     * POST /me/registration/checkout — ensure subscription + Razorpay order for the selected package.
     *
     * @return array<string, mixed>
     */
    public function prepareRegistrationCheckout(User $user, Package $package): array
    {
        $packageId = (int) $package->id;
        $payable = $package->registrationPayableAmountRupees();

        if ($payable <= 0) {
            $this->attachSubscriptionForRegistration($user->id, $packageId, 'active', 'system');
            $user->refresh();
            $this->packagePermissionService->syncCandidatePermissions($user);

            return [
                'skip_checkout' => true,
                'reason' => 'free_or_complimentary',
            ];
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->first();

        if ($subscription instanceof Subscription && $subscription->subscription_status === 'active') {
            return [
                'skip_checkout' => true,
                'reason' => 'already_subscribed',
            ];
        }

        $subscriptionId = $this->findOrCreateRegistrationSubscriptionForPaidPackage($user->id, $packageId);

        /** @var Payment|null $pendingPayment */
        $pendingPayment = Payment::query()
            ->where('subscription_id', $subscriptionId)
            ->where('gateway_name', 'razorpay')
            ->where('payment_status', 'pending')
            ->whereNotNull('gateway_order_id')
            ->orderByDesc('id')
            ->first();

        if ($pendingPayment instanceof Payment) {
            $amountPaise = (int) round(((float) $pendingPayment->amount) * 100);

            return [
                'skip_checkout' => false,
                'order_id' => (string) $pendingPayment->gateway_order_id,
                'key_id' => (string) config('services.razorpay.key_id', ''),
                'amount_paise' => $amountPaise,
                'currency' => strtoupper((string) $pendingPayment->currency),
                'payment_uuid' => (string) $pendingPayment->uuid,
                'checkout_options' => config('services.razorpay.checkout', []),
            ];
        }

        $meta = $this->registrationPaymentService->createOrderForRegistration($user, $package, $subscriptionId);

        return [
            'skip_checkout' => false,
            'order_id' => $meta['orderId'],
            'key_id' => $meta['keyId'],
            'amount_paise' => $meta['amount'],
            'currency' => $meta['currency'],
            'payment_uuid' => $meta['paymentUuid'],
            'checkout_options' => config('services.razorpay.checkout', []),
        ];
    }

    /**
     * GET /me/registration/status — onboarding gate for payment + KYC.
     *
     * @return array<string, mixed>
     */
    public function registrationStatusForMember(User $user, ?string $packageUuidQuery): array
    {
        $package = $this->resolveRegistrationPackageForStatus($user, $packageUuidQuery);
        $paymentBlock = $package instanceof Package
            ? $this->buildRegistrationPaymentStatusBlock($user, $package)
            : [
                'resolved' => false,
                'message' => 'Pass package_uuid query or complete a registration checkout to resolve payment state.',
            ];

        $aadhaarRaw = $user->verificationDocuments()->where('document_type', KycDocumentService::DOCUMENT_AADHAAR)->first();
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
        $existing = DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->first();

        $uuid = $existing !== null && isset($existing->uuid) ? (string) $existing->uuid : (string) Str::uuid();

        $row = [
            'uuid' => $uuid,
            'subscription_status' => $subscriptionStatus,
            'started_at' => $now,
            'ends_at' => $now->copy()->addYear(),
            'auto_renew' => false,
            'renewal_source' => $renewalSource,
            'updated_at' => $now,
        ];

        if ($existing === null) {
            $row['created_at'] = $now;
            $id = (int) DB::table('subscriptions')->insertGetId(
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

    private function findOrCreateRegistrationSubscriptionForPaidPackage(int $userId, int $packageId): int
    {
        $existing = DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->first();

        if ($existing !== null) {
            return (int) $existing->id;
        }

        return $this->attachSubscriptionForRegistration($userId, $packageId, 'pending', 'gateway');
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
        $packageId = (int) $package->id;
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
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->first();

        if (!$subscription instanceof Subscription) {
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
            ->where('gateway_name', 'razorpay')
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
            'awaiting_checkout' => $latestPayment === null
                || (
                    (string) $latestPayment->payment_status === 'pending'
                    && $latestPayment->gateway_order_id === null
                ),
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

        if (!$payableResolved || (!(bool) ($paymentBlock['skip_checkout'] ?? false) && ($subscriptionStatus === 'pending' || $subscriptionStatus === ''))) {
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
