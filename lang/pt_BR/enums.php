<?php

return [
    'channel_type' => [
        'sales' => 'Vendas',
        'support' => 'Suporte',
    ],
    'conversation_status' => [
        'active' => 'Ativa',
        'closed' => 'Fechada',
        'escalated' => 'Escalada',
    ],
    'lead_status' => [
        'new' => 'Novo',
        'contacted' => 'Contatado',
        'converted' => 'Convertido',
        'lost' => 'Perdido',
    ],
    'document_status' => [
        'pending' => 'Pendente',
        'processing' => 'Processando',
        'ready' => 'Pronto',
        'failed' => 'Falhou',
    ],
    'integration_status' => [
        'active' => 'Ativa',
        'suspended' => 'Suspensa',
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
        'active' => 'Ativa',
        'trialing' => 'Teste',
        'past_due' => 'Vencida',
        'canceled' => 'Cancelada',
        'expired' => 'Expirada',
    ],
    'payment_status' => [
        'pending' => 'Pendente',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
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
        'conversation.started' => 'Conversa iniciada',
        'message.received' => 'Mensagem recebida',
        'lead.captured' => 'Lead capturado',
        'escalation.triggered' => 'Escalação acionada',
        'conversation.closed' => 'Conversa encerrada',
    ],
];
