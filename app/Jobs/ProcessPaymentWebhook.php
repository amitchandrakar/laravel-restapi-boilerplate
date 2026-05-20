<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresQueueRetries;
use App\Services\Payment\RegistrationPaymentService;
use App\Support\QueuePriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentWebhook implements ShouldQueue
{
    use ConfiguresQueueRetries;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(private readonly array $payload)
    {
        $this->onQueue(QueuePriority::critical());
    }

    public function handle(RegistrationPaymentService $registrationPaymentService): void
    {
        $registrationPaymentService->handleWebhookEvent($this->payload);
    }
}
