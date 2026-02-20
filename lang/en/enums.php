<?php

return [
    'channel_type' => [
        'sales' => 'Sales',
        'support' => 'Support',
    ],
    'conversation_status' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'escalated' => 'Escalated',
    ],
    'lead_status' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'converted' => 'Converted',
        'lost' => 'Lost',
    ],
    'document_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'ready' => 'Ready',
        'failed' => 'Failed',
    ],
    'integration_status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],
    'integration_provider' => [
        'n8n' => 'n8n',
        'zapier' => 'Zapier',
        'make' => 'Make',
        'custom' => 'Custom',
    ],
    'billing_interval' => [
        'quarterly' => 'Quarterly',
        'semi_annual' => 'Semi Annual',
        'annual' => 'Annual',
    ],
    'subscription_status' => [
        'active' => 'Active',
        'trialing' => 'Trialing',
        'past_due' => 'Past Due',
        'canceled' => 'Canceled',
        'expired' => 'Expired',
    ],
    'payment_status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
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
    'webhook_event' => [
        'conversation.started' => 'Conversation Started',
        'message.received' => 'Message Received',
        'lead.captured' => 'Lead Captured',
        'escalation.triggered' => 'Escalation Triggered',
        'conversation.closed' => 'Conversation Closed',
    ],
];
