<?php

namespace App\Services\Billing;

use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Services\Billing\Contracts\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualPaymentProvider implements PaymentProvider
{
    public function createCustomer(Tenant $tenant): string
    {
        return $tenant->id;
    }

    public function createSubscription(Tenant $tenant, PlanPrice $planPrice): SubscriptionResult
    {
        return SubscriptionResult::success(
            externalSubscriptionId: 'manual_'.Str::uuid(),
        );
    }

    public function cancelSubscription(string $externalSubscriptionId): bool
    {
        return true;
    }

    public function handleWebhook(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            eventType: 'manual',
            externalPaymentId: $request->input('payment_id'),
            externalSubscriptionId: $request->input('subscription_id'),
            status: $request->input('status', 'completed'),
            amount: $request->input('amount'),
            currency: $request->input('currency', 'PEN'),
            rawPayload: $request->all(),
        );
    }
}
