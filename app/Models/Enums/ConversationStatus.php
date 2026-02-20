<?php

namespace App\Models\Enums;

enum ConversationStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Escalated = 'escalated';

    public function label(): string
    {
        return __('enums.conversation_status.'.$this->value);
    }
}
