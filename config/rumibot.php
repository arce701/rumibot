<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'default_provider' => env('RUMIBOT_DEFAULT_AI_PROVIDER', 'openai'),
        'default_model' => env('RUMIBOT_DEFAULT_AI_MODEL', 'gpt-4o-mini'),
        'fallback_providers' => ['openai', 'anthropic'],
        'temperature' => 0.7,
        'max_tokens' => 500,
        'max_conversation_messages' => 50,
        'timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Base System Prompt
    |--------------------------------------------------------------------------
    |
    | Foundation instructions for all tenant agents. The tenant's system_prompt
    | and channel's system_prompt_override are appended after this.
    |
    */

    'base_prompt' => <<<'PROMPT'
Eres un asistente virtual profesional que representa a un negocio. Sigue estas reglas fundamentales:

1. **Idioma**: Responde SIEMPRE en el mismo idioma del usuario. Por defecto, responde en español latinoamericano.
2. **Tono**: Sé amable, profesional y conciso. Usa un tono cálido pero no excesivamente informal.
3. **Límite**: Responde SOLO sobre temas relacionados con el negocio que representas. Si te preguntan sobre temas no relacionados, redirige amablemente la conversación.
4. **Formato**: Usa mensajes cortos y claros, ideales para WhatsApp (máximo 2-3 párrafos).
5. **Datos sensibles**: NUNCA compartas información confidencial del negocio como precios internos, estrategias o datos de otros clientes.
6. **Escalación**: Si el usuario insiste en hablar con un humano, tiene un problema complejo, o estás inseguro de la respuesta correcta, escala la conversación.
7. **Herramientas**: Usa las herramientas disponibles cuando sea apropiado (enviar medios, capturar datos del prospecto, escalar).
PROMPT,

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'default_provider' => env('RUMIBOT_WHATSAPP_PROVIDER', 'ycloud'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */

    'billing' => [
        'default_provider' => env('RUMIBOT_BILLING_PROVIDER', 'mercadopago'),
        'currency' => env('RUMIBOT_BILLING_CURRENCY', 'PEN'),
        'trial_days' => env('RUMIBOT_TRIAL_DAYS', 14),
        'grace_period_days' => env('RUMIBOT_GRACE_PERIOD_DAYS', 3),
        'mercadopago' => [
            'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        ],
    ],

];
