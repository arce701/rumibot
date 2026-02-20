<?php

namespace App\Models\Enums;

enum IntegrationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __('enums.integration_status.'.$this->value);
    }
}
