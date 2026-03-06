<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\TrackTokenUsage;
use App\Ai\Tools\CaptureLead;
use App\Ai\Tools\EscalateToHuman;
use App\Ai\Tools\SendMedia;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ChannelType;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeChunk;
use App\Models\Tenant;
use App\Support\PhoneHelper;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

#[MaxTokens(500)]
#[Temperature(0.7)]
class TenantChatAgent implements Agent, Conversational, HasMiddleware, HasTools
{
    use Promptable;

    public function __construct(
        public Tenant $tenant,
        public Channel $channel,
        public Conversation $conversation,
    ) {}

    public function instructions(): Stringable|string
    {
        return implode("\n\n", array_filter([
            config('rumibot.base_prompt'),
            $this->tenant->system_prompt,
            $this->channel->system_prompt_override,
            $this->buildCountryContext(),
        ]));
    }

    public function messages(): iterable
    {
        $limit = $this->tenant->ai_context_window ?? 50;

        return $this->conversation->messages()
            ->withoutGlobalScopes()
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($msg) => new Message($msg->role, $msg->content))
            ->all();
    }

    /**
     * @return \Laravel\Ai\Contracts\Tool[]
     */
    public function tools(): iterable
    {
        $tenantId = $this->tenant->id;
        $channelId = $this->channel->id;

        $tools = [
            SimilaritySearch::usingModel(
                model: KnowledgeChunk::class,
                column: 'embedding',
                query: fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereHas('document', fn ($q) => $q
                        ->withoutGlobalScopes()
                        ->where('status', DocumentStatus::Ready)
                        ->where(fn ($q) => $q
                            ->whereJsonLength('channel_scope', 0)
                            ->orWhereJsonContains('channel_scope', $channelId)
                        )
                    ),
            )->withDescription('Search the knowledge base for relevant information to answer the user question.'),
            new SendMedia($this->channel, $this->conversation),
            new EscalateToHuman($this->conversation),
        ];

        if ($this->channel->type === ChannelType::Sales) {
            $tools[] = new CaptureLead($this->conversation);
        }

        return $tools;
    }

    private function buildCountryContext(): ?string
    {
        $iso = $this->conversation->contact_country;

        if (! $iso) {
            $iso = PhoneHelper::detectCountryIso($this->conversation->contact_phone);
        }

        if (! $iso) {
            return null;
        }

        $name = PhoneHelper::countryNameFromIso($iso);

        if (! $name) {
            return null;
        }

        $phone = $this->conversation->contact_phone;
        $formatted = PhoneHelper::format($phone);

        return "Contexto: El prospecto escribe desde {$name} ({$formatted}). Ya conoces su pais — no lo preguntes. Usa esta informacion para la captura de leads y para mostrar precios/metodos de pago del pais correcto.";
    }

    public function middleware(): array
    {
        return [
            new TrackTokenUsage($this->conversation),
        ];
    }
}
