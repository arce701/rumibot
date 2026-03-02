<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsApp\WhatsAppWebhookHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public Conversation $conversation,
        public string $text,
        public ?int $messageId = null,
        public ?string $mediaType = null,
        public ?string $mediaPath = null,
        public ?string $mediaFilename = null,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        Context::add('tenant_id', $this->conversation->tenant_id);

        $channel = $this->conversation->channel;
        $handler = app(WhatsAppWebhookHandler::class);
        $provider = $handler->resolveProvider($channel);

        $from = $channel->provider_phone_number_id;
        $to = $this->conversation->contact_phone;
        $caption = $this->text !== '' ? $this->text : null;

        $response = match ($this->mediaType) {
            'image' => $provider->sendImage(
                from: $from,
                to: $to,
                url: Storage::disk('s3')->temporaryUrl($this->mediaPath, now()->addHour()),
                caption: $caption,
            ),
            'document' => $provider->sendDocument(
                from: $from,
                to: $to,
                url: Storage::disk('s3')->temporaryUrl($this->mediaPath, now()->addHour()),
                filename: $this->mediaFilename,
                caption: $caption,
            ),
            default => $provider->sendText(
                from: $from,
                to: $to,
                text: $this->text,
            ),
        };

        if ($response->success) {
            if ($this->messageId) {
                Message::withoutGlobalScopes()
                    ->where('id', $this->messageId)
                    ->update([
                        'metadata' => array_merge(
                            Message::withoutGlobalScopes()->find($this->messageId)?->metadata ?? [],
                            ['whatsapp_message_id' => $response->messageId],
                        ),
                    ]);
            } else {
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
            }
        } else {
            Log::error('Failed to send WhatsApp message', [
                'conversation_id' => $this->conversation->id,
                'error' => $response->error,
            ]);

            $this->fail(new \RuntimeException("WhatsApp send failed: {$response->error}"));
        }
    }
}
