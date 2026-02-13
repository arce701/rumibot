<?php

namespace App\Services\Billing;

readonly class WebhookPayload
{
    public function __construct(
        public string $eventType,
        public ?string $externalPaymentId,
        public ?string $externalSubscriptionId,
        public ?string $status,
        public ?int $amount,
        public ?string $currency,
        public array $rawPayload,
    ) {}
}
