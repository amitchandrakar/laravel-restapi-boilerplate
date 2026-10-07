<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(private readonly CouponPricingService $couponPricingService) {}

    /**
     * @return Collection<int, Coupon>
     */
    public function listForAdmin(): Collection
    {
        $query = Coupon::query()->with('package:id,uuid,name,code');
        $query->getQuery()->orderBy('id', 'desc');

        return $query->get();
    }

    public function findByUuid(string $uuid): ?Coupon
    {
        return Coupon::query()->where('uuid', $uuid)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Coupon
    {
        $coupon = new Coupon();
        $this->fillFromPayload($coupon, $data);
        $coupon->uses_count = 0;
        $coupon->save();

        return $coupon->fresh(['package:id,uuid,name,code']) ?? $coupon;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        $this->fillFromPayload($coupon, $data);
        $coupon->save();

        return $coupon->fresh(['package:id,uuid,name,code']) ?? $coupon;
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    /**
     * @return array{valid: bool, originalPrice: int, discountedPrice: int, coupon: array<string, mixed>|null, message?: string}
     */
    public function validateForPackage(Package $package, string $couponCode, ?User $user = null): array
    {
        $original = (int) floor($package->catalogRegistrationPayableAmountRupees());

        try {
            $coupon = $this->resolveEligibleCoupon($package, $couponCode, $user);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            return [
                'valid' => false,
                'originalPrice' => $original,
                'discountedPrice' => $original,
                'coupon' => null,
                'message' => is_string($message) ? $message : 'Coupon is not valid.',
            ];
        }

        $discounted = $this->couponPricingService->apply($package, $coupon);

        return [
            'valid' => true,
            'originalPrice' => $original,
            'discountedPrice' => $discounted,
            'coupon' => $this->toPublicCouponArray($coupon),
        ];
    }

    /**
     * @return Collection<int, Coupon>
     */
    public function availableForPackage(Package $package): Collection
    {
        $query = $this->availableQuery($package->id);
        $query->getQuery()->orderBy('name');

        return $query->get();
    }

    /**
     * Prefer the eligible coupon that yields the lowest payable amount for the package.
     */
    public function bestEligibleCouponForPackage(Package $package, ?User $user = null): ?Coupon
    {
        $candidates = $this->availableForPackage($package);

        if ($candidates->isEmpty()) {
            return null;
        }

        $best = null;
        $bestPayable = null;

        foreach ($candidates as $coupon) {
            try {
                $this->assertCouponEligible($coupon, $package, $user);
            } catch (ValidationException) {
                continue;
            }

            $payable = $this->couponPricingService->apply($package, $coupon);

            if ($bestPayable === null || $payable < $bestPayable) {
                $best = $coupon;
                $bestPayable = $payable;
            }
        }

        return $best;
    }

    public function resolveEligibleCoupon(Package $package, string $couponCode, ?User $user = null): Coupon
    {
        $normalized = $this->normalizeCode($couponCode);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'coupon_code' => ['Coupon code is required.'],
            ]);
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()->where('code', $normalized)->first();

        if (!($coupon instanceof Coupon)) {
            throw ValidationException::withMessages([
                'coupon_code' => ['Coupon code is not recognized.'],
            ]);
        }

        $this->assertCouponEligible($coupon, $package, $user);

        return $coupon;
    }

    public function redeem(Coupon $coupon, int $userId, int $subscriptionId): void
    {
        $couponId = $coupon->id;

        DB::transaction(function () use ($couponId, $userId, $subscriptionId): void {
            $lockedQuery = Coupon::query()->whereKey($couponId);
            $lockedQuery->getQuery()->lockForUpdate();
            $locked = $lockedQuery->first();

            if (!($locked instanceof Coupon)) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['Coupon is no longer available.'],
                ]);
            }

            if ($locked->max_uses !== null && $locked->uses_count >= $locked->max_uses) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['This coupon has reached its usage limit.'],
                ]);
            }

            $existing = CouponRedemption::query()
                ->where('coupon_id', $locked->id)
                ->where('user_id', $userId)
                ->toBase()
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['You have already used this coupon.'],
                ]);
            }

            $now = now();
            CouponRedemption::query()->create([
                'coupon_id' => $locked->id,
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'redeemed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $locked->increment('uses_count');
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(Coupon $coupon): array
    {
        $package = $coupon->package;

        return [
            'uuid' => $coupon->uuid,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discountType' => $coupon->discount_type,
            'discountValue' => $coupon->discount_value,
            'packageId' => $coupon->package_id !== null ? $coupon->package_id : null,
            'packageUuid' => $package !== null ? $package->uuid : null,
            'packageName' => $package !== null ? $package->name : null,
            'startsAt' => $coupon->starts_at !== null ? Carbon::parse($coupon->starts_at)->toIso8601String() : null,
            'expiresAt' => $coupon->expires_at !== null ? Carbon::parse($coupon->expires_at)->toIso8601String() : null,
            'maxUses' => $coupon->max_uses !== null ? $coupon->max_uses : null,
            'usesCount' => $coupon->uses_count,
            'isActive' => $coupon->is_active,
            'createdAt' => $coupon->created_at?->toIso8601String(),
            'updatedAt' => $coupon->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicCouponArray(Coupon $coupon): array
    {
        return [
            'uuid' => $coupon->uuid,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discountType' => $coupon->discount_type,
            'discountValue' => $coupon->discount_value,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillFromPayload(Coupon $coupon, array $data): void
    {
        if (array_key_exists('code', $data)) {
            $coupon->code = $this->normalizeCode((string) $data['code']);
        }

        if (array_key_exists('name', $data)) {
            $coupon->name = (string) $data['name'];
        }

        if (array_key_exists('discountType', $data)) {
            $discountType = $data['discountType'];

            if ($discountType !== 'fixed' && $discountType !== 'percent') {
                throw ValidationException::withMessages([
                    'discountType' => ['Discount type must be percent or fixed.'],
                ]);
            }

            $coupon->discount_type = $discountType;
        }

        if (array_key_exists('discountValue', $data)) {
            $coupon->discount_value = $this->nonNegativeInt($data['discountValue'], 'discountValue');
        }

        if (array_key_exists('packageUuid', $data)) {
            $packageUuid = $data['packageUuid'];

            if ($packageUuid === null || $packageUuid === '') {
                $coupon->package_id = null;
            } else {
                $coupon->package_id = $this->nullableNonNegativeInt(
                    Package::query()->where('uuid', (string) $packageUuid)->value('id'),
                    'packageUuid'
                );
            }
        } elseif (array_key_exists('packageId', $data)) {
            $coupon->package_id = $this->nullableNonNegativeInt($data['packageId'], 'packageId');
        }

        if (array_key_exists('startsAt', $data)) {
            $starts = $data['startsAt'];
            $coupon->starts_at = $starts === null || $starts === '' ? null : Carbon::parse((string) $starts);
        }

        if (array_key_exists('expiresAt', $data)) {
            $expires = $data['expiresAt'];
            $coupon->expires_at = $expires === null || $expires === '' ? null : Carbon::parse((string) $expires);
        }

        if (array_key_exists('maxUses', $data)) {
            $coupon->max_uses = $this->nullableNonNegativeInt($data['maxUses'], 'maxUses');
        }

        if (array_key_exists('isActive', $data)) {
            $coupon->is_active = (bool) $data['isActive'];
        }
    }

    /**
     * @return Builder<Coupon>
     */
    private function availableQuery(int $packageId): Builder
    {
        $now = now();

        return Coupon::query()
            ->where('is_active', true)
            ->where(static function (Builder $query) use ($packageId): void {
                $query->where('package_id', null)->orWhere('package_id', $packageId);
            })
            ->where(static function (Builder $query) use ($now): void {
                $query->where('starts_at', null)->orWhere('starts_at', '<=', $now);
            })
            ->where(static function (Builder $query) use ($now): void {
                $query->where('expires_at', null)->orWhere('expires_at', '>', $now);
            })
            ->where(static function (Builder $query): void {
                $query->where('max_uses', null);
                $query->getQuery()->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    /**
     * @return int<0, max>
     */
    private function nonNegativeInt(mixed $value, string $field): int
    {
        if (is_int($value)) {
            $parsed = $value;
        } elseif (is_string($value) && is_numeric($value)) {
            $parsed = (int) $value;
        } else {
            throw ValidationException::withMessages([
                $field => ['A non-negative integer is required.'],
            ]);
        }

        if ($parsed < 0) {
            throw ValidationException::withMessages([
                $field => ['A non-negative integer is required.'],
            ]);
        }

        return $parsed;
    }

    /**
     * @return int<0, max>|null
     */
    private function nullableNonNegativeInt(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->nonNegativeInt($value, $field);
    }

    private function assertCouponEligible(Coupon $coupon, Package $package, ?User $user = null): void
    {
        if (!$coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon is not active.'],
            ]);
        }

        if ($coupon->starts_at !== null && Carbon::parse($coupon->starts_at)->isFuture()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon is not active yet.'],
            ]);
        }

        if ($coupon->expires_at !== null && Carbon::parse($coupon->expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses !== null && $coupon->uses_count >= $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has reached its usage limit.'],
            ]);
        }

        if ($coupon->package_id !== null && $coupon->package_id !== $package->id) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon does not apply to the selected package.'],
            ]);
        }

        if ($user !== null) {
            $alreadyUsed = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->toBase()
                ->exists();

            if ($alreadyUsed) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['You have already used this coupon.'],
                ]);
            }
        }
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
