<?php

namespace App\Models\Enums;

enum WebhookEvent: string
{
    case ConversationStarted = 'conversation.started';
    case MessageReceived = 'message.received';
    case LeadCaptured = 'lead.captured';
    case EscalationTriggered = 'escalation.triggered';
    case ConversationClosed = 'conversation.closed';
}
