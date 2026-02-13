<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingMessage;
use App\Services\WhatsApp\WhatsAppWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsAppWebhookHandler $handler,
    ) {}

    /**
     * Handle webhook verification (GET).
     * Used by WhatsApp providers to verify the webhook URL.
     */
    public function verify(Request $request, string $tenantUuid, string $channelSlug): Response
    {
        $channel = $this->handler->resolveChannel($tenantUuid, $channelSlug);

        if (! $channel) {
            return response('Not found', 404);
        }

        $challenge = $request->query('hub_challenge', $request->query('challenge', ''));
        $verifyToken = $request->query('hub_verify_token', $request->query('token', ''));

        if ($verifyToken !== $channel->provider_webhook_verify_token) {
            Log::warning('Webhook verification failed', [
                'tenant_id' => $tenantUuid,
                'channel_slug' => $channelSlug,
            ]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Handle incoming webhook events (POST).
     */
    public function receive(Request $request, string $tenantUuid, string $channelSlug): JsonResponse
    {
        $channel = $this->handler->resolveChannel($tenantUuid, $channelSlug);

        if (! $channel) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $payload = $request->all();

        if (! $this->handler->isInboundMessageEvent($payload)) {
            return response()->json(['status' => 'ignored']);
        }

        $inboundMessage = $this->handler->parseInboundMessage($channel, $payload);

        ProcessIncomingMessage::dispatch($channel, $inboundMessage);

        return response()->json(['status' => 'queued']);
    }
}
