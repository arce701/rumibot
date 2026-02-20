<?php

namespace App\Models\Enums;

enum WhatsAppProviderType: string
{
    case YCloud = 'ycloud';
    case MetaCloud = 'meta_cloud';

    public function label(): string
    {
        return __('enums.whatsapp_provider.'.$this->value);
    }
}
