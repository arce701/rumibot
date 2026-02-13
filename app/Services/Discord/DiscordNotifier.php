<?php

namespace App\Services\Discord;

use App\Models\Escalation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotifier
{
    /**
     * @param  array{embeds: array<int, array{title: string, color: int, fields: array<int, array{name: string, value: string, inline?: bool}>, timestamp: string}>}  $payload
     */
    public function sendEscalationEmbed(Escalation $escalation, string $webhookUrl): void
    {
        $conversation = $escalation->conversation;

        $fields = [
            ['name' => 'Contacto', 'value' => $conversation->contact_name ?? $conversation->contact_phone, 'inline' => true],
            ['name' => 'Canal', 'value' => $conversation->channel?->name ?? 'N/A', 'inline' => true],
            ['name' => 'Razón', 'value' => str_replace('_', ' ', ucfirst($escalation->reason)), 'inline' => true],
        ];

        if ($escalation->note) {
            $fields[] = ['name' => 'Nota', 'value' => $escalation->note];
        }

        $payload = [
            'embeds' => [
                [
                    'title' => 'Nueva Escalación',
                    'color' => 15548997, // Red (#ED4245)
                    'fields' => $fields,
                    'timestamp' => $escalation->created_at->toIso8601String(),
                ],
            ],
        ];

        try {
            Http::post($webhookUrl, $payload);
        } catch (\Exception $e) {
            Log::warning('Discord webhook failed', [
                'escalation_id' => $escalation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
