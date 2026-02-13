<?php

namespace App\Services\WhatsApp;

use App\Models\Channel;
use App\Models\Tenant;
use App\Services\WhatsApp\Contracts\WhatsAppProvider;

class WhatsAppWebhookHandler
{
    public function resolveChannel(string $tenantUuid, string $channelSlug): ?Channel
    {
        $tenant = Tenant::withoutGlobalScopes()->where('id', $tenantUuid)->where('is_active', true)->first();

        if (! $tenant) {
            return null;
        }

        return Channel::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $channelSlug)
            ->where('is_active', true)
            ->first();
    }

    public function parseInboundMessage(Channel $channel, array $payload): InboundMessage
    {
        $provider = $this->resolveProvider($channel);

        return $provider->parseInboundMessage($payload);
    }

    public function isInboundMessageEvent(array $payload): bool
    {
        return ($payload['type'] ?? '') === 'whatsapp.inbound_message.received';
    }

    private function resolveProvider(Channel $channel): WhatsAppProvider
    {
        return new YCloudProvider($channel->provider_api_key);
    }
}
