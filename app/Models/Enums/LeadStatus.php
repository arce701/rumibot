<?php

namespace App\Models\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return __('enums.lead_status.'.$this->value);
    }
}
