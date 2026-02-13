<?php

namespace App\Services\WhatsApp;

class InboundMessage
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $from,
        public readonly string $to,
        public readonly string $type,
        public readonly string $content,
        public readonly ?string $contactName = null,
        public readonly ?string $timestamp = null,
        public readonly ?array $media = null,
        public readonly ?array $context = null,
        public readonly array $rawPayload = [],
    ) {}

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document', 'sticker']);
    }

    public function isInteractive(): bool
    {
        return in_array($this->type, ['interactive', 'button']);
    }
}
