<?php

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\InboundMessage;
use App\Services\WhatsApp\MessageResponse;

interface WhatsAppProvider
{
    public function sendText(string $from, string $to, string $text): MessageResponse;

    public function sendImage(string $from, string $to, string $url, ?string $caption = null): MessageResponse;

    public function sendDocument(string $from, string $to, string $url, string $filename, ?string $caption = null): MessageResponse;

    public function sendInteractive(string $from, string $to, string $body, array $buttons): MessageResponse;

    public function parseInboundMessage(array $payload): InboundMessage;
}
