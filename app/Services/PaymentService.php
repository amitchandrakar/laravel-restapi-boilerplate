<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Support\QuerySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public const PAYMENT_STATUSES = ['pending', 'success', 'failed', 'refunded', 'cancelled'];

    public const PAYMENT_METHODS = ['upi', 'card', 'netbanking', 'wallet', 'cash', 'manual'];

    public const SORT_OPTIONS = ['latest', 'oldest', 'amount'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            /** @var Payment $payment */
            $payment = Payment::query()->create($this->normalizePayload($data));
            $this->syncSubscriptionForPayment($payment);
            $this->appendPaymentHistory($payment, null);

            Log::info('PaymentService: payment created', ['payment_id' => $payment->id]);

            return $payment->refresh()->load(['user', 'package']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePayment(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data): Payment {
            $previousStatus = (string) $payment->payment_status;
            $payment->update($this->normalizePayload($data));
            $payment = $payment->refresh();
            $this->syncSubscriptionForPayment($payment);
            $this->appendPaymentHistory($payment, $previousStatus);

            Log::info('PaymentService: payment updated', ['payment_id' => $payment->id]);

            return $payment->load(['user', 'package']);
        });
    }

    public function deletePayment(Payment $payment): void
    {
        $payment->delete();
        Log::info('PaymentService: payment deleted', ['payment_id' => $payment->id]);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));

        return $this->buildListQuery($filters)
            ->with(['user', 'package'])
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Builder<Payment>
     */
    public function buildListQuery(array $filters = []): Builder
    {
        $query = Payment::query();

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(static function (Builder $builder) use ($search): void {
                QuerySearch::whereContainsAny(
                    $builder,
                    ['gateway_order_id', 'gateway_payment_id', 'gateway_reference_id'],
                    $search
                );
                $builder->orWhereHas('user', static function (Builder $userQuery) use ($search): void {
                    if (!$userQuery->getModel() instanceof User) {
                        return;
                    }

                    QuerySearch::whereContainsAny($userQuery, ['email'], $search);
                });
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['package_id'])) {
            $query->where('package_id', (int) $filters['package_id']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', (string) $filters['payment_status']);
        }

        if (!empty($filters['gateway_name'])) {
            $query->where('gateway_name', (string) $filters['gateway_name']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', (string) $filters['payment_method']);
        }

        if (!empty($filters['paid_from'])) {
            $query->where('paid_at', '>=', Carbon::parse((string) $filters['paid_from'])->startOfDay());
        }

        if (!empty($filters['paid_to'])) {
            $query->where('paid_at', '<=', Carbon::parse((string) $filters['paid_to'])->endOfDay());
        }

        $sort = (string) ($filters['sort'] ?? 'latest');

        return match ($sort) {
            'oldest' => $query->orderBy('id'),
            'amount' => $query->orderByDesc('amount')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $payload = [];

        foreach (
            [
                'user_id',
                'subscription_id',
                'package_id',
                'gateway_name',
                'gateway_order_id',
                'gateway_payment_id',
                'gateway_reference_id',
                'payment_status',
                'payment_method',
                'failed_reason',
                'raw_response_json',
                'paid_at',
                'webhook_event_id',
            ] as $field
        ) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('amount', $data)) {
            $payload['amount'] = (float) $data['amount'];
        }

        if (array_key_exists('currency', $data)) {
            $payload['currency'] = strtoupper((string) $data['currency']);
        }

        return $payload;
    }

    private function syncSubscriptionForPayment(Payment $payment): void
    {
        if ($payment->subscription_id === null) {
            return;
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()->find($payment->subscription_id);

        if (!($subscription instanceof Subscription)) {
            return;
        }

        $subscription->last_payment_id = $payment->id;
        $subscription->subscription_status = $this->resolveSubscriptionStatus((string) $payment->payment_status);
        $subscription->save();
    }

    private function resolveSubscriptionStatus(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'success' => 'active',
            'refunded' => 'cancelled',
            'failed', 'cancelled' => 'pending',
            default => 'pending',
        };
    }

    private function appendPaymentHistory(Payment $payment, ?string $previousStatus): void
    {
        $historyType = $this->resolveHistoryType((string) $payment->payment_status, $previousStatus);

        DB::table('user_payment_history')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'history_type' => $historyType,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'remarks' => $previousStatus === null ? 'Payment created via admin CRUD.' : 'Payment updated via admin CRUD.',
            'created_at' => now(),
        ]);
    }

    private function resolveHistoryType(string $currentStatus, ?string $previousStatus): string
    {
        if ($previousStatus === null && $currentStatus === 'pending') {
            return 'initiated';
        }

        return match ($currentStatus) {
            'success' => 'confirmed',
            'failed', 'cancelled' => 'failed',
            'refunded' => $previousStatus === 'refunded' ? 'refunded' : 'refund_initiated',
            default => 'initiated',
        };
    }
}
