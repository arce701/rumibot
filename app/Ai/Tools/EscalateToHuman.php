<?php

namespace App\Ai\Tools;

use App\Events\EscalationTriggered;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EscalateToHuman implements Tool
{
    public function __construct(
        private Conversation $conversation,
    ) {}

    public function description(): Stringable|string
    {
        return 'Escalate the conversation to a human agent. Use this when: the user explicitly asks to speak with a human, the question is too complex or outside your knowledge, the user expresses frustration or dissatisfaction, or you need human authorization for a sensitive action.';
    }

    public function handle(Request $request): Stringable|string
    {
        $escalation = Escalation::create([
            'tenant_id' => $this->conversation->tenant_id,
            'conversation_id' => $this->conversation->id,
            'reason' => $request['reason'],
            'note' => $request['note'] ?? null,
        ]);

        $this->conversation->update([
            'status' => ConversationStatus::Escalated,
        ]);

        $escalation->load('conversation.channel', 'conversation.tenant');

        event(new EscalationTriggered($escalation));

        return 'Conversation escalated to a human agent. The user will be notified that a team member will follow up shortly.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reason' => $schema->string()->enum([
                'customer_request',
                'complex_question',
                'negative_sentiment',
                'sensitive_action',
                'outside_knowledge',
            ])->required(),
            'note' => $schema->string(),
        ];
    }
}
