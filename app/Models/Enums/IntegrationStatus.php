<?php

namespace App\Models\Enums;

enum IntegrationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
