<?php

namespace App\Services\WhatsApp;

use App\Models\Channel;
use App\Models\Tenant;
use App\Services\WhatsApp\Contracts\WhatsAppProvider;

class WhatsAppWebhookHandler
{
    public function resolveChannelByPhoneNumberId(string $tenantUuid, string $phoneNumberId): ?Channel
    {
        $tenant = Tenant::withoutGlobalScopes()->where('id', $tenantUuid)->where('is_active', true)->first();

        if (! $tenant) {
            return null;
        }

        return Channel::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('provider_phone_number_id', $phoneNumberId)
            ->where('is_active', true)
            ->first();
    }

    public function extractPhoneNumberId(array $payload): ?string
    {
        return $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
    }

    public function parseInboundMessage(Channel $channel, array $payload): InboundMessage
    {
        $provider = $this->resolveProvider($channel);

        return $provider->parseInboundMessage($payload);
    }

    public function isInboundMessageEvent(array $payload): bool
    {
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return false;
        }

        $change = $payload['entry'][0]['changes'][0] ?? [];

        return ($change['field'] ?? '') === 'messages'
            && ! empty($change['value']['messages']);
    }

    public function resolveProvider(Channel $channel): WhatsAppProvider
    {
        return new MetaCloudProvider($channel->provider_api_key, $channel->provider_phone_number_id);
    }
}
