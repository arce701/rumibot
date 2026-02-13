<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Services\Billing\MercadoPagoProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function mercadopago(Request $request, MercadoPagoProvider $provider): JsonResponse
    {
        try {
            $payload = $provider->handleWebhook($request);

            if (! $payload->externalSubscriptionId) {
                return response()->json(['status' => 'ignored']);
            }

            $subscription = Subscription::withoutGlobalScopes()
                ->where('external_subscription_id', $payload->externalSubscriptionId)
                ->first();

            if (! $subscription) {
                Log::warning('Webhook received for unknown subscription', [
                    'external_subscription_id' => $payload->externalSubscriptionId,
                ]);

                return response()->json(['status' => 'ignored']);
            }

            if ($payload->status === 'completed') {
                $subscription->update(['status' => SubscriptionStatus::Active]);
            } elseif ($payload->status === 'failed') {
                $subscription->update(['status' => SubscriptionStatus::PastDue]);
            }

            PaymentHistory::withoutGlobalScopes()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'payment_provider' => $subscription->payment_provider,
                'external_payment_id' => $payload->externalPaymentId,
                'status' => match ($payload->status) {
                    'completed' => PaymentStatus::Completed,
                    'failed' => PaymentStatus::Failed,
                    'pending' => PaymentStatus::Pending,
                    default => PaymentStatus::Pending,
                },
                'amount' => $payload->amount ?? 0,
                'currency' => $payload->currency ?? 'PEN',
                'description' => "Webhook: {$payload->eventType}",
                'metadata' => $payload->rawPayload,
            ]);

            return response()->json(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error('Payment webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
