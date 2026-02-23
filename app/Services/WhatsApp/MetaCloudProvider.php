<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudProvider implements WhatsAppProvider
{
    public function __construct(
        private string $accessToken,
        private string $phoneNumberId,
    ) {}

    public function sendText(string $from, string $to, string $text): MessageResponse
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $text,
            ],
        ]);
    }

    public function sendImage(string $from, string $to, string $url, ?string $caption = null): MessageResponse
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $url,
                'caption' => $caption,
            ]),
        ]);
    }

    public function sendDocument(string $from, string $to, string $url, string $filename, ?string $caption = null): MessageResponse
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => array_filter([
                'link' => $url,
                'filename' => $filename,
                'caption' => $caption,
            ]),
        ]);
    }

    public function sendInteractive(string $from, string $to, string $body, array $buttons): MessageResponse
    {
        $formattedButtons = collect($buttons)->map(fn (array $button, int $index) => [
            'type' => 'reply',
            'reply' => [
                'id' => $button['id'] ?? "button_{$index}",
                'title' => $button['title'],
            ],
        ])->values()->all();

        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $formattedButtons],
            ],
        ]);
    }

    public function parseInboundMessage(array $payload): InboundMessage
    {
        $value = $payload['entry'][0]['changes'][0]['value'] ?? [];
        $message = $value['messages'][0] ?? [];
        $contact = $value['contacts'][0] ?? [];

        $content = match ($message['type'] ?? 'text') {
            'text' => $message['text']['body'] ?? '',
            'image' => $message['image']['caption'] ?? '[image]',
            'document' => $message['document']['caption'] ?? '[document]',
            'video' => $message['video']['caption'] ?? '[video]',
            'audio' => '[audio]',
            'interactive' => $this->extractInteractiveContent($message),
            'button' => $message['button']['text'] ?? '',
            default => '[unsupported message type]',
        };

        $media = null;
        $type = $message['type'] ?? 'text';

        if (in_array($type, ['image', 'video', 'audio', 'document', 'sticker'])) {
            $media = $message[$type] ?? null;
        }

        $timestamp = isset($message['timestamp'])
            ? date('c', (int) $message['timestamp'])
            : null;

        return new InboundMessage(
            messageId: $message['id'] ?? '',
            from: $message['from'] ?? '',
            to: $value['metadata']['display_phone_number'] ?? '',
            type: $type,
            content: $content,
            contactName: $contact['profile']['name'] ?? null,
            timestamp: $timestamp,
            media: $media,
            context: $message['context'] ?? null,
            rawPayload: $payload,
        );
    }

    private function sendMessage(array $payload): MessageResponse
    {
        try {
            $response = $this->httpClient()
                ->post("/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                return MessageResponse::success(
                    messageId: $response->json('messages.0.id', ''),
                    rawResponse: $response->json(),
                );
            }

            Log::warning('Meta Cloud API message send failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return MessageResponse::failure(
                error: $response->json('error.message', 'Unknown error'),
                rawResponse: $response->json(),
            );
        } catch (\Exception $e) {
            Log::error('Meta Cloud API exception', ['error' => $e->getMessage()]);

            return MessageResponse::failure(error: $e->getMessage());
        }
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl('https://graph.facebook.com/'.config('rumibot.whatsapp.api_version'))
            ->withToken($this->accessToken)
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 100, throw: false);
    }

    private function extractInteractiveContent(array $message): string
    {
        $interactive = $message['interactive'] ?? [];

        if (isset($interactive['list_reply'])) {
            return $interactive['list_reply']['title'] ?? '';
        }

        if (isset($interactive['button_reply'])) {
            return $interactive['button_reply']['title'] ?? '';
        }

        return '';
    }
}
