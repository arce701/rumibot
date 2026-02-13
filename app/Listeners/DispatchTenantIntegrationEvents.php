<?php

namespace App\Listeners;

use App\Events\ConversationClosed;
use App\Events\ConversationStarted;
use App\Events\EscalationTriggered;
use App\Events\LeadCaptured;
use App\Events\MessageReceived;
use App\Jobs\DispatchIntegrationEvent;
use App\Models\Enums\IntegrationStatus;
use App\Models\Enums\WebhookEvent;
use App\Models\TenantIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Pennant\Feature;

class DispatchTenantIntegrationEvents implements ShouldQueue
{
    public function handle(
        ConversationStarted|MessageReceived|LeadCaptured|EscalationTriggered|ConversationClosed $event,
    ): void {
        $model = $this->resolveModel($event);
        $tenantId = $model->tenant_id;

        if (! Feature::for(null)->active('webhook-integrations')) {
            return;
        }

        $webhookEvent = $this->resolveWebhookEvent($event);

        $payload = [
            'event' => $webhookEvent->value,
            'timestamp' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
            'data' => $model->toArray(),
        ];

        $integrations = TenantIntegration::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', IntegrationStatus::Active)
            ->get()
            ->filter(fn (TenantIntegration $integration) => $integration->isSubscribedToEvent($webhookEvent->value));

        foreach ($integrations as $integration) {
            DispatchIntegrationEvent::dispatch($integration, $payload);
        }
    }

    private function resolveModel(
        ConversationStarted|MessageReceived|LeadCaptured|EscalationTriggered|ConversationClosed $event,
    ): mixed {
        return match (true) {
            $event instanceof ConversationStarted => $event->conversation,
            $event instanceof MessageReceived => $event->message,
            $event instanceof LeadCaptured => $event->lead,
            $event instanceof EscalationTriggered => $event->escalation,
            $event instanceof ConversationClosed => $event->conversation,
        };
    }

    private function resolveWebhookEvent(
        ConversationStarted|MessageReceived|LeadCaptured|EscalationTriggered|ConversationClosed $event,
    ): WebhookEvent {
        return match (true) {
            $event instanceof ConversationStarted => WebhookEvent::ConversationStarted,
            $event instanceof MessageReceived => WebhookEvent::MessageReceived,
            $event instanceof LeadCaptured => WebhookEvent::LeadCaptured,
            $event instanceof EscalationTriggered => WebhookEvent::EscalationTriggered,
            $event instanceof ConversationClosed => WebhookEvent::ConversationClosed,
        };
    }
}
