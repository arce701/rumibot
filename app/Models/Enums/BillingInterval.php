<?php

namespace App\Models\Enums;

enum BillingInterval: string
{
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual = 'annual';
}
