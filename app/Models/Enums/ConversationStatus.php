<?php

namespace App\Models\Enums;

enum ConversationStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Escalated = 'escalated';
}
