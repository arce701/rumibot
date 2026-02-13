<?php

namespace App\Models\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Converted = 'converted';
    case Lost = 'lost';
}
