<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\PaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RegistrationPaymentService
{
    public function __construct(
        private readonly RazorpayClient $razorpay,
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Create a pending payment row and a Razorpay order for registration checkout.
     *
     * @return array{paymentUuid: string, orderId: string, keyId: string, amount: int, currency: string, packageName: string}
     */
    public function createOrderForRegistration(User $user, Package $package, int $subscriptionId): array
    {
        $amountRupees = $package->registrationPayableAmountRupees();
        if ($amountRupees <= 0) {
            throw new \InvalidArgumentException('Package has no payable amount; use free registration path.');
        }

        $currency = strtoupper((string) ($package->currency ?? config('services.razorpay.currency', 'INR')));
        $amountPaise = (int) round($amountRupees * 100);

        /** @var Payment $payment */
        $payment = $this->paymentService->createPayment([
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'package_id' => $package->id,
            'gateway_name' => 'razorpay',
            'amount' => $amountRupees,
            'currency' => $currency,
            'payment_status' => 'pending',
            'payment_method' => 'upi',
        ]);

        $receipt = substr(str_replace('-', '', (string) $payment->uuid), 0, 40);
        $order = $this->razorpay->createOrder($amountPaise, $currency, $receipt, [
            'user_id' => (string) $user->id,
            'payment_uuid' => (string) $payment->uuid,
            'package_id' => (string) $package->id,
        ]);

        $orderId = isset($order['id']) && is_string($order['id']) ? $order['id'] : null;
        if ($orderId === null || $orderId === '') {
            throw new \RuntimeException('Razorpay order did not return an id.');
        }

        $payment->forceFill(['gateway_order_id' => $orderId])->save();

        $keyId = (string) config('services.razorpay.key_id', '');

        return [
            'paymentUuid' => (string) $payment->uuid,
            'orderId' => $orderId,
            'keyId' => $keyId,
            'amount' => $amountPaise,
            'currency' => $currency,
            'packageName' => (string) $package->name,
            'checkoutOptions' => config('services.razorpay.checkout', []),
        ];
    }

    /**
     * Confirm checkout after Razorpay Checkout returns payment id + signature (member-authenticated).
     */
    public function confirmCheckoutPayment(User $user, string $orderId, string $paymentId, string $signature): Payment
    {
        $ok = $this->razorpay->verifyCheckoutSignature([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);
        if (!$ok) {
            throw ValidationException::withMessages([
                'razorpay_signature' => ['Invalid payment signature.'],
            ]);
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('user_id', $user->id)
            ->where('gateway_order_id', $orderId)
            ->where('gateway_name', 'razorpay')
            ->first();

        if (!$payment instanceof Payment) {
            throw ValidationException::withMessages([
                'razorpay_order_id' => ['No matching registration payment was found.'],
            ]);
        }

        if ($payment->payment_status === 'success') {
            throw new ConflictHttpException('This payment was already confirmed.');
        }

        if ($payment->payment_status !== 'pending') {
            throw new ConflictHttpException('This payment cannot be confirmed.');
        }

        $this->finalizeSucceeded($payment, $paymentId, null, [
            'source' => 'checkout_confirm',
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
        ]);

        return $payment->refresh();
    }

    /**
     * @param  array<string, mixed>  $decodedBody  Decoded Razorpay webhook JSON
     */
    public function handleWebhookEvent(array $decodedBody): void
    {
        $event = data_get($decodedBody, 'event');
        if (!is_string($event)) {
            return;
        }

        $eventId = data_get($decodedBody, 'id');
        $eventIdStr = is_string($eventId) ? $eventId : null;

        $entity = data_get($decodedBody, 'payload.payment.entity');
        if (!is_array($entity)) {
            return;
        }

        $orderId = data_get($entity, 'order_id');
        if (!is_string($orderId) || $orderId === '') {
            return;
        }

        $paymentId = data_get($entity, 'id');
        $paymentIdStr = is_string($paymentId) ? $paymentId : null;

        /** @var Payment|null $payment */
        $payment = Payment::query()->where('gateway_order_id', $orderId)->where('gateway_name', 'razorpay')->first();
        if (!$payment instanceof Payment) {
            return;
        }

        if ($event === 'payment.authorized') {
            return;
        }

        if ($event === 'payment.captured') {
            if ($paymentIdStr === null) {
                return;
            }
            if ($payment->payment_status === 'success') {
                return;
            }
            if ($payment->payment_status !== 'pending') {
                return;
            }
            if ($eventIdStr !== null && $payment->webhook_event_id !== null && $payment->webhook_event_id === $eventIdStr) {
                return;
            }

            try {
                $this->finalizeSucceeded($payment, $paymentIdStr, $eventIdStr, [
                    'source' => 'webhook',
                    'event' => $event,
                    'entity' => $entity,
                ]);
            } catch (QueryException $e) {
                if ($this->isDuplicateWebhookEventId($e)) {
                    return;
                }
                throw $e;
            }

            return;
        }

        if ($event === 'payment.failed') {
            if ($payment->payment_status !== 'pending') {
                return;
            }
            $reason = data_get($entity, 'error_description');
            $reasonStr = is_string($reason) ? $reason : 'Payment failed.';

            try {
                $this->finalizeFailed($payment, $reasonStr, $eventIdStr, [
                    'source' => 'webhook',
                    'event' => $event,
                    'entity' => $entity,
                ]);
            } catch (QueryException $e) {
                if ($this->isDuplicateWebhookEventId($e)) {
                    return;
                }
                throw $e;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(User $user, string $paymentUuid): array
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()->where('uuid', $paymentUuid)->where('user_id', $user->id)->first();
        if (!$payment instanceof Payment) {
            throw ValidationException::withMessages([
                'paymentUuid' => ['Payment not found.'],
            ]);
        }

        $subscription = $payment->subscription;
        $endsAt = null;
        if ($subscription instanceof Subscription && $subscription->ends_at !== null) {
            $endsAt = Carbon::parse($subscription->ends_at)->toIso8601String();
        }

        return [
            'paymentUuid' => $payment->uuid,
            'paymentStatus' => $payment->payment_status,
            'orderId' => $payment->gateway_order_id,
            'subscription' => [
                'status' => $subscription instanceof Subscription ? $subscription->subscription_status : null,
                'endsAt' => $endsAt,
            ],
        ];
    }

    private function finalizeSucceeded(Payment $payment, string $gatewayPaymentId, ?string $webhookEventId, array $raw): void
    {
        DB::transaction(function () use ($payment, $gatewayPaymentId, $webhookEventId, $raw): void {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === 'success') {
                return;
            }
            if ($locked->payment_status !== 'pending') {
                return;
            }

            $mergedRaw = array_merge($locked->raw_response_json ?? [], $raw);

            $this->paymentService->updatePayment($locked, [
                'gateway_payment_id' => $gatewayPaymentId,
                'payment_status' => 'success',
                'payment_method' => 'upi',
                'paid_at' => now(),
                'raw_response_json' => $mergedRaw,
                'webhook_event_id' => $webhookEventId ?? $locked->webhook_event_id,
                'failed_reason' => null,
            ]);

            $locked = $locked->refresh();
            /** @var User|null $user */
            $user = User::query()->find($locked->user_id);
            if ($user instanceof User) {
                $user->notify(new PaymentSucceededNotification($locked));
            }
        });
    }

    private function finalizeFailed(Payment $payment, string $reason, ?string $webhookEventId, array $raw): void
    {
        DB::transaction(function () use ($payment, $reason, $webhookEventId, $raw): void {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== 'pending') {
                return;
            }

            $mergedRaw = array_merge($locked->raw_response_json ?? [], $raw);

            $this->paymentService->updatePayment($locked, [
                'payment_status' => 'failed',
                'failed_reason' => $reason,
                'raw_response_json' => $mergedRaw,
                'webhook_event_id' => $webhookEventId ?? $locked->webhook_event_id,
            ]);

            $locked = $locked->refresh();
            /** @var User|null $user */
            $user = User::query()->find($locked->user_id);
            if ($user instanceof User) {
                $user->notify(new PaymentFailedNotification($locked, $reason));
            }
        });
    }

    private function isDuplicateWebhookEventId(QueryException $e): bool
    {
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        // MySQL 1062 / SQLite 19: unique constraint (webhook_event_id)
        if ($code === 1062 || $code === 19) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'unique');
    }
}
