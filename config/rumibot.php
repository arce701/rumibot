<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */

    'ai' => [
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
4. **Formato**: Usa mensajes cortos y claros, ideales para WhatsApp (máximo 2-3 párrafos). Usa *negritas* con asteriscos para resaltar puntos clave.
5. **Datos sensibles**: NUNCA compartas información confidencial del negocio como precios internos, estrategias o datos de otros clientes.
6. **Escalación**: Si el usuario insiste en hablar con un humano, tiene un problema complejo, o estás inseguro de la respuesta correcta, usa la herramienta EscalateToHuman.
7. **Captura de leads**: Cuando el usuario comparta su nombre, correo, empresa o país, o muestre interés en un producto/servicio, usa la herramienta CaptureLead de inmediato para registrar esos datos. No esperes a tener todos los datos — captura lo que tengas y actualiza después. Puedes llamar esta herramienta varias veces en la misma conversación a medida que obtengas más información.
8. **Recolección natural de datos**: Durante la conversación, busca oportunidades naturales para preguntar el nombre del usuario, a qué se dedica su empresa, y su correo electrónico. No lo hagas todo de golpe — intercala las preguntas de forma conversacional entre las respuestas de valor que le des.
PROMPT,

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'api_version' => env('RUMIBOT_WHATSAPP_API_VERSION', 'v21.0'),
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
