<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
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

            return $payment->refresh();
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

            return $payment;
        });
    }

    public function deletePayment(Payment $payment): void
    {
        $payment->delete();
    }

    public function paginatePayments(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        return Payment::query()->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
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
        if (!$subscription instanceof Subscription) {
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
