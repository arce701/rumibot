<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YCloudProvider implements WhatsAppProvider
{
    private const BASE_URL = 'https://api.ycloud.com/v2';

    public function __construct(
        private string $apiKey,
    ) {}

    public function sendText(string $from, string $to, string $text): MessageResponse
    {
        return $this->sendMessage([
            'from' => $from,
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $text,
            ],
        ]);
    }

    public function sendImage(string $from, string $to, string $url, ?string $caption = null): MessageResponse
    {
        $payload = [
            'from' => $from,
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $url,
                'caption' => $caption,
            ]),
        ];

        return $this->sendMessage($payload);
    }

    public function sendDocument(string $from, string $to, string $url, string $filename, ?string $caption = null): MessageResponse
    {
        $payload = [
            'from' => $from,
            'to' => $to,
            'type' => 'document',
            'document' => array_filter([
                'link' => $url,
                'filename' => $filename,
                'caption' => $caption,
            ]),
        ];

        return $this->sendMessage($payload);
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
            'from' => $from,
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
        $message = $payload['whatsappInboundMessage'] ?? [];

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

        return new InboundMessage(
            messageId: $message['id'] ?? '',
            from: $message['from'] ?? '',
            to: $message['to'] ?? '',
            type: $type,
            content: $content,
            contactName: $message['customerProfile']['name'] ?? null,
            timestamp: $message['sendTime'] ?? null,
            media: $media,
            context: $message['context'] ?? null,
            rawPayload: $payload,
        );
    }

    private function sendMessage(array $payload): MessageResponse
    {
        try {
            $response = $this->httpClient()
                ->post('/whatsapp/messages/sendDirectly', $payload);

            if ($response->successful()) {
                return MessageResponse::success(
                    messageId: $response->json('id', ''),
                    rawResponse: $response->json(),
                );
            }

            Log::warning('YCloud message send failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return MessageResponse::failure(
                error: $response->json('message', 'Unknown error'),
                rawResponse: $response->json(),
            );
        } catch (\Exception $e) {
            Log::error('YCloud API exception', ['error' => $e->getMessage()]);

            return MessageResponse::failure(error: $e->getMessage());
        }
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders(['X-API-Key' => $this->apiKey])
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
