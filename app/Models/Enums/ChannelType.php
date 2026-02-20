<?php

namespace App\Models\Enums;

enum ChannelType: string
{
    case Sales = 'sales';
    case Support = 'support';

    public function label(): string
    {
        return __('enums.channel_type.'.$this->value);
    }
}
