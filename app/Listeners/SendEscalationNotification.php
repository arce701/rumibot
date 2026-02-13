<?php

namespace App\Listeners;

use App\Events\EscalationTriggered;
use App\Services\Discord\DiscordNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEscalationNotification implements ShouldQueue
{
    public function __construct(
        private DiscordNotifier $notifier,
    ) {}

    public function handle(EscalationTriggered $event): void
    {
        $escalation = $event->escalation;

        $webhookUrl = $escalation->conversation->tenant->settings['discord_webhook_url']
            ?? config('services.discord.webhook_url');

        if (! $webhookUrl) {
            return;
        }

        $this->notifier->sendEscalationEmbed($escalation, $webhookUrl);
    }
}
