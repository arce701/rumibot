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

    public static function generateVerifyToken(string $tenantUuid): string
    {
        return hash_hmac('sha256', $tenantUuid, config('app.key'));
    }

    /**
     * Handle Meta webhook verification (GET).
     */
    public function verify(Request $request, string $tenantUuid): Response
    {
        $mode = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge', '');
        $verifyToken = $request->query('hub_verify_token', '');

        if ($mode !== 'subscribe' || $verifyToken !== static::generateVerifyToken($tenantUuid)) {
            Log::warning('Webhook verification failed', [
                'tenant_id' => $tenantUuid,
            ]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Handle incoming Meta webhook events (POST).
     */
    public function receive(Request $request, string $tenantUuid): JsonResponse
    {
        $payload = $request->all();

        if (! $this->handler->isInboundMessageEvent($payload)) {
            return response()->json(['status' => 'ignored']);
        }

        $phoneNumberId = $this->handler->extractPhoneNumberId($payload);

        if (! $phoneNumberId) {
            return response()->json(['error' => 'Missing phone_number_id'], 400);
        }

        $channel = $this->handler->resolveChannelByPhoneNumberId($tenantUuid, $phoneNumberId);

        if (! $channel) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $inboundMessage = $this->handler->parseInboundMessage($channel, $payload);

        ProcessIncomingMessage::dispatch($channel, $inboundMessage);

        return response()->json(['status' => 'queued']);
    }
}
