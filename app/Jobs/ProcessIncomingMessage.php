<?php

namespace App\Jobs;

use App\Ai\Agents\TenantChatAgent;
use App\Events\ConversationStarted;
use App\Events\MessageReceived;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use App\Services\WhatsApp\InboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public int $timeout = 180;

    public function __construct(
        public Channel $channel,
        public InboundMessage $inboundMessage,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        Context::add('tenant_id', $this->channel->tenant_id);

        $conversation = $this->findOrCreateConversation();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $this->channel->tenant_id,
            'role' => 'user',
            'content' => $this->inboundMessage->content,
            'metadata' => array_filter([
                'whatsapp_message_id' => $this->inboundMessage->messageId,
                'message_type' => $this->inboundMessage->type,
                'media' => $this->inboundMessage->media,
            ]),
        ]);

        event(new MessageReceived($message));

        $conversation->increment('messages_count');
        $conversation->update(['last_message_at' => now()]);

        Log::info('Incoming message processed', [
            'conversation_id' => $conversation->id,
            'channel_id' => $this->channel->id,
            'from' => $this->inboundMessage->from,
        ]);

        $this->generateAiResponse($conversation);
    }

    private function generateAiResponse(Conversation $conversation): void
    {
        $tenant = $this->channel->tenant;

        $credential = $tenant->defaultLlmCredential;
        if (! $credential) {
            Log::warning('No LLM credential configured for tenant', [
                'tenant_id' => $tenant->id,
                'channel_id' => $this->channel->id,
            ]);

            return;
        }

        $agent = new TenantChatAgent($tenant, $this->channel, $conversation);

        config()->set("ai.providers.{$credential->provider->value}.key", $credential->api_key);

        $provider = $credential->provider->value;
        $model = $this->channel->ai_model_override ?? $tenant->default_ai_model;

        if (! $model) {
            Log::warning('No AI model configured for tenant', [
                'tenant_id' => $tenant->id,
                'channel_id' => $this->channel->id,
            ]);

            return;
        }

        $response = $agent->prompt(
            $this->inboundMessage->content,
            provider: $provider,
            model: $model,
        );

        $responseText = (string) $response;

        Message::create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $tenant->id,
            'role' => 'assistant',
            'content' => $responseText,
            'model_used' => $model,
            'tokens_input' => $response->usage->promptTokens ?? null,
            'tokens_output' => $response->usage->completionTokens ?? null,
            'metadata' => [
                'provider' => $provider,
            ],
        ]);

        $conversation->increment('messages_count');
        $conversation->update(['last_message_at' => now()]);

        SendWhatsAppMessage::dispatch($conversation, $responseText);
    }

    private function findOrCreateConversation(): Conversation
    {
        $conversation = Conversation::withoutGlobalScopes()
            ->where('channel_id', $this->channel->id)
            ->where('contact_phone', $this->inboundMessage->from)
            ->where('status', ConversationStatus::Active)
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'tenant_id' => $this->channel->tenant_id,
            'channel_id' => $this->channel->id,
            'contact_phone' => $this->inboundMessage->from,
            'contact_name' => $this->inboundMessage->contactName,
            'status' => ConversationStatus::Active,
            'metadata' => [],
        ]);

        event(new ConversationStarted($conversation));

        return $conversation;
    }
}
