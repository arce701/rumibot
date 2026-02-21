<?php

return [
    'channel_type' => [
        'sales' => 'Ventas',
        'support' => 'Soporte',
    ],
    'conversation_status' => [
        'active' => 'Activa',
        'closed' => 'Cerrada',
        'escalated' => 'Escalada',
    ],
    'lead_status' => [
        'new' => 'Nuevo',
        'contacted' => 'Contactado',
        'converted' => 'Convertido',
        'lost' => 'Perdido',
    ],
    'document_status' => [
        'pending' => 'Pendiente',
        'processing' => 'Procesando',
        'ready' => 'Listo',
        'failed' => 'Fallido',
    ],
    'integration_status' => [
        'active' => 'Activa',
        'suspended' => 'Suspendida',
    ],
    'integration_provider' => [
        'n8n' => 'n8n',
        'zapier' => 'Zapier',
        'make' => 'Make',
        'custom' => 'Personalizado',
    ],
    'billing_interval' => [
        'quarterly' => 'Trimestral',
        'semi_annual' => 'Semestral',
        'annual' => 'Anual',
    ],
    'subscription_status' => [
        'active' => 'Activa',
        'trialing' => 'Prueba',
        'past_due' => 'Vencida',
        'canceled' => 'Cancelada',
        'expired' => 'Expirada',
    ],
    'payment_status' => [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
    ],
    'payment_provider' => [
        'mercadopago' => 'MercadoPago',
        'stripe' => 'Stripe',
        'manual' => 'Manual',
    ],
    'whatsapp_provider' => [
        'ycloud' => 'YCloud',
        'meta_cloud' => 'Meta Cloud',
    ],
    'ai_provider' => [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'gemini' => 'Google Gemini',
        'groq' => 'Groq',
        'deepseek' => 'DeepSeek',
        'mistral' => 'Mistral',
        'xai' => 'xAI',
        'openrouter' => 'OpenRouter',
    ],
    'webhook_event' => [
        'conversation.started' => 'Conversación iniciada',
        'message.received' => 'Mensaje recibido',
        'lead.captured' => 'Lead capturado',
        'escalation.triggered' => 'Escalación activada',
        'conversation.closed' => 'Conversación cerrada',
    ],
];
