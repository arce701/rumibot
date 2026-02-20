<?php

namespace App\Models\Enums;

enum BillingInterval: string
{
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual = 'annual';

    public function label(): string
    {
        return __('enums.billing_interval.'.$this->value);
    }
}
