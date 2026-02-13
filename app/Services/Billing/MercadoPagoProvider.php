<?php

namespace App\Services\Billing;

use App\Models\Enums\BillingInterval;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Services\Billing\Contracts\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoProvider implements PaymentProvider
{
    public function __construct(
        private string $accessToken,
    ) {
        MercadoPagoConfig::setAccessToken($this->accessToken);
    }

    public function createCustomer(Tenant $tenant): string
    {
        return $tenant->id;
    }

    public function createSubscription(Tenant $tenant, PlanPrice $planPrice): SubscriptionResult
    {
        try {
            $client = new PreApprovalClient;

            $frequency = $this->mapBillingInterval($planPrice->billing_interval);

            $response = $client->create([
                'reason' => $planPrice->plan->name.' - '.$planPrice->billing_interval->value,
                'auto_recurring' => [
                    'frequency' => $frequency['frequency'],
                    'frequency_type' => $frequency['frequency_type'],
                    'transaction_amount' => $planPrice->price_amount / 100,
                    'currency_id' => $planPrice->currency,
                ],
                'back_url' => url('/billing'),
                'payer_email' => $tenant->users()->first()?->email ?? '',
            ]);

            return SubscriptionResult::success(
                externalSubscriptionId: $response->id,
                checkoutUrl: $response->init_point,
            );
        } catch (\Throwable $e) {
            Log::error('MercadoPago subscription creation failed', [
                'tenant_id' => $tenant->id,
                'plan_price_id' => $planPrice->id,
                'error' => $e->getMessage(),
            ]);

            return SubscriptionResult::failure($e->getMessage());
        }
    }

    public function cancelSubscription(string $externalSubscriptionId): bool
    {
        try {
            $client = new PreApprovalClient;
            $client->update($externalSubscriptionId, ['status' => 'cancelled']);

            return true;
        } catch (\Throwable $e) {
            Log::error('MercadoPago subscription cancellation failed', [
                'external_id' => $externalSubscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function handleWebhook(Request $request): WebhookPayload
    {
        $payload = $request->all();

        return new WebhookPayload(
            eventType: $payload['type'] ?? 'unknown',
            externalPaymentId: $payload['data']['id'] ?? null,
            externalSubscriptionId: $payload['data']['metadata']['preapproval_id'] ?? null,
            status: $this->mapPaymentStatus($payload['data']['status'] ?? null),
            amount: isset($payload['data']['transaction_amount'])
                ? (int) ($payload['data']['transaction_amount'] * 100)
                : null,
            currency: $payload['data']['currency_id'] ?? null,
            rawPayload: $payload,
        );
    }

    /**
     * @return array{frequency: int, frequency_type: string}
     */
    private function mapBillingInterval(BillingInterval $interval): array
    {
        return match ($interval) {
            BillingInterval::Quarterly => ['frequency' => 3, 'frequency_type' => 'months'],
            BillingInterval::SemiAnnual => ['frequency' => 6, 'frequency_type' => 'months'],
            BillingInterval::Annual => ['frequency' => 12, 'frequency_type' => 'months'],
        };
    }

    private function mapPaymentStatus(?string $status): ?string
    {
        return match ($status) {
            'approved' => 'completed',
            'pending', 'in_process' => 'pending',
            'rejected', 'cancelled' => 'failed',
            default => $status,
        };
    }
}
