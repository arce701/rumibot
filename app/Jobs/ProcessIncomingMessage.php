<?php

namespace App\Jobs;

use App\Ai\Agents\TenantChatAgent;
use App\Events\ConversationStarted;
use App\Events\MessageReceived;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\AiProvider;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use App\Services\WhatsApp\InboundMessage;
use App\Support\PhoneHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class ProcessIncomingMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 120, 300];

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

        $this->storeIncomingMessage($conversation);

        $this->generateAiResponse($conversation);
    }

    private function storeIncomingMessage(Conversation $conversation): Message
    {
        if ($this->inboundMessage->messageId) {
            $existing = Message::withoutGlobalScopes()
                ->where('conversation_id', $conversation->id)
                ->where('metadata->whatsapp_message_id', $this->inboundMessage->messageId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

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

        return $message;
    }

    private function generateAiResponse(Conversation $conversation): void
    {
        if ($conversation->isAiPaused()) {
            Log::info('AI response skipped — conversation paused for human intervention', [
                'conversation_id' => $conversation->id,
                'ai_paused_until' => $conversation->ai_paused_until,
            ]);

            return;
        }

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

        try {
            $response = $agent->prompt(
                $this->inboundMessage->content,
                provider: $provider,
                model: $model,
            );
        } catch (RateLimitedException $e) {
            $delay = $this->resolveRetryAfter($e, $provider);

            Log::warning('AI provider rate limited, releasing job for retry', [
                'conversation_id' => $conversation->id,
                'provider' => $provider,
                'attempt' => $this->attempts(),
                'retry_in_seconds' => $delay,
            ]);

            $this->release($delay);

            return;
        }

        $responseText = (string) $response;

        $assistantMessage = Message::create([
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

        SendWhatsAppMessage::dispatch($conversation, $responseText, $assistantMessage->id);
    }

    private function resolveRetryAfter(RateLimitedException $e, string $provider): int
    {
        $previous = $e->getPrevious();

        if ($previous instanceof RequestException && $previous->response) {
            $retryAfter = $previous->response->header('Retry-After')
                ?? $previous->response->header('retry-after');

            if ($retryAfter && is_numeric($retryAfter)) {
                return max(1, (int) $retryAfter);
            }
        }

        $aiProvider = AiProvider::tryFrom($provider);

        return $aiProvider?->rateLimitCooldownSeconds() ?? 60;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessIncomingMessage permanently failed', [
            'channel_id' => $this->channel->id,
            'from' => $this->inboundMessage->from,
            'exception' => $exception?->getMessage(),
        ]);
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
            'contact_country' => PhoneHelper::detectCountryIso($this->inboundMessage->from),
            'status' => ConversationStatus::Active,
            'metadata' => [],
        ]);

        event(new ConversationStarted($conversation));

        return $conversation;
    }
}
