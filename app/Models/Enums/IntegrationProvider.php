<?php

namespace App\Models\Enums;

enum IntegrationProvider: string
{
    case N8n = 'n8n';
    case Zapier = 'zapier';
    case Make = 'make';
    case Custom = 'custom';
}
