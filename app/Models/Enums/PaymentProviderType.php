<?php

namespace App\Models\Enums;

enum PaymentProviderType: string
{
    case MercadoPago = 'mercadopago';
    case Stripe = 'stripe';
    case Manual = 'manual';

    public function label(): string
    {
        return __('enums.payment_provider.'.$this->value);
    }
}
