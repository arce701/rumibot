<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsApp\WhatsAppWebhookHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public Conversation $conversation,
        public string $text,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        Context::add('tenant_id', $this->conversation->tenant_id);

        $channel = $this->conversation->channel;
        $handler = app(WhatsAppWebhookHandler::class);
        $provider = $handler->resolveProvider($channel);

        $response = $provider->sendText(
            from: $channel->provider_phone_number_id,
            to: $this->conversation->contact_phone,
            text: $this->text,
        );

        if ($response->success) {
            Message::create([
                'conversation_id' => $this->conversation->id,
                'tenant_id' => $this->conversation->tenant_id,
                'role' => 'assistant',
                'content' => $this->text,
                'metadata' => [
                    'whatsapp_message_id' => $response->messageId,
                    'provider' => 'meta_cloud',
                ],
            ]);

            $this->conversation->increment('messages_count');
            $this->conversation->update(['last_message_at' => now()]);
        } else {
            Log::error('Failed to send WhatsApp message', [
                'conversation_id' => $this->conversation->id,
                'error' => $response->error,
            ]);

            $this->fail(new \RuntimeException("WhatsApp send failed: {$response->error}"));
        }
    }
}
