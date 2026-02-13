<?php

namespace App\Services\Billing;

readonly class SubscriptionResult
{
    public function __construct(
        public bool $success,
        public ?string $externalSubscriptionId = null,
        public ?string $checkoutUrl = null,
        public ?string $error = null,
    ) {}

    public static function success(string $externalSubscriptionId, ?string $checkoutUrl = null): self
    {
        return new self(
            success: true,
            externalSubscriptionId: $externalSubscriptionId,
            checkoutUrl: $checkoutUrl,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
