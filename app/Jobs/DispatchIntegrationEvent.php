<?php

namespace App\Jobs;

use App\Models\Enums\IntegrationStatus;
use App\Models\TenantIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchIntegrationEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 120];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public TenantIntegration $integration,
        public array $payload,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Context::add('tenant_id', $this->integration->tenant_id);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Rumibot-Webhook/1.0',
        ];

        if ($this->integration->secret) {
            $signature = hash_hmac('sha256', json_encode($this->payload), $this->integration->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        $response = Http::withHeaders($headers)
            ->timeout(15)
            ->post($this->integration->url, $this->payload);

        if ($response->successful()) {
            $this->integration->update([
                'failure_count' => 0,
                'last_triggered_at' => now(),
            ]);

            return;
        }

        Log::warning('Integration webhook failed', [
            'integration_id' => $this->integration->id,
            'status' => $response->status(),
            'url' => $this->integration->url,
        ]);

        $failureCount = $this->integration->failure_count + 1;

        if ($failureCount >= 5) {
            $this->integration->update([
                'failure_count' => $failureCount,
                'status' => IntegrationStatus::Suspended,
            ]);

            Log::error('Integration suspended due to repeated failures', [
                'integration_id' => $this->integration->id,
            ]);

            return;
        }

        $this->integration->update(['failure_count' => $failureCount]);

        $this->fail(new \RuntimeException("Webhook failed with status {$response->status()}"));
    }
}
