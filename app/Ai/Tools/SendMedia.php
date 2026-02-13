<?php

namespace App\Ai\Tools;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SendMedia implements Tool
{
    public function __construct(
        private Channel $channel,
        private Conversation $conversation,
    ) {}

    public function description(): Stringable|string
    {
        return 'Send a media message (image, document, or video link) to the user via WhatsApp. Use this when the user asks for visual content, product photos, brochures, guides, or video demonstrations.';
    }

    public function handle(Request $request): Stringable|string
    {
        $type = $request['type'];
        $url = $request['url'];
        $caption = $request['caption'] ?? null;

        // For now, send the media reference as a text message with the URL
        // Full media sending will be implemented when we add sendImage/sendDocument to the job
        $text = match ($type) {
            'image' => $caption ? "{$caption}\n{$url}" : $url,
            'document' => $caption ? "{$caption}\n{$url}" : $url,
            'video' => $caption ? "{$caption}\n{$url}" : $url,
            default => $url,
        };

        SendWhatsAppMessage::dispatch($this->conversation, $text);

        return "Media message ({$type}) sent successfully.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['image', 'document', 'video'])->required(),
            'url' => $schema->string()->required(),
            'caption' => $schema->string(),
        ];
    }
}
