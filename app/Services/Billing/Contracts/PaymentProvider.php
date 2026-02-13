<?php

namespace App\Services\Billing\Contracts;

use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionResult;
use App\Services\Billing\WebhookPayload;
use Illuminate\Http\Request;

interface PaymentProvider
{
    public function createCustomer(Tenant $tenant): string;

    public function createSubscription(Tenant $tenant, PlanPrice $planPrice): SubscriptionResult;

    public function cancelSubscription(string $externalSubscriptionId): bool;

    public function handleWebhook(Request $request): WebhookPayload;
}
