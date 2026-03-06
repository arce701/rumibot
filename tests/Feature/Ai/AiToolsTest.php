<?php

use App\Ai\Tools\CaptureLead;
use App\Ai\Tools\EscalateToHuman;
use App\Ai\Tools\SendMedia;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
    ]);
});

// --- CaptureLead Tests ---

test('capture lead creates a new lead', function () {
    $tool = new CaptureLead($this->conversation);

    $result = $tool->handle(new Request([
        'full_name' => 'Juan Pérez',
        'email' => 'juan@test.com',
        'company_name' => 'Mi Empresa',
        'country' => 'Perú',
    ]));

    $lead = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($lead)->not->toBeNull();
    expect($lead->full_name)->toBe('Juan Pérez');
    expect($lead->email)->toBe('juan@test.com');
    expect($lead->company_name)->toBe('Mi Empresa');
    expect($lead->country)->toBe('Perú');
    expect($lead->phone)->toBe('+51999888777');
    expect($lead->tenant_id)->toBe($this->tenant->id);
    expect((string) $result)->toContain('Juan Pérez');
});

test('capture lead updates existing lead', function () {
    Lead::create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'phone' => '+51999888777',
        'full_name' => 'Juan',
    ]);

    $tool = new CaptureLead($this->conversation);

    $tool->handle(new Request([
        'full_name' => 'Juan Pérez García',
        'email' => 'juan@empresa.com',
    ]));

    $leads = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->get();

    expect($leads)->toHaveCount(1);
    expect($leads->first()->full_name)->toBe('Juan Pérez García');
    expect($leads->first()->email)->toBe('juan@empresa.com');
});

test('capture lead merges interests without duplicates', function () {
    Lead::create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'phone' => '+51999888777',
        'interests' => ['software', 'consultoría'],
    ]);

    $tool = new CaptureLead($this->conversation);

    $tool->handle(new Request([
        'interests' => ['software', 'capacitación'],
    ]));

    $lead = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($lead->interests)->toContain('software')
        ->toContain('consultoría')
        ->toContain('capacitación')
        ->toHaveCount(3);
});

test('capture lead uses conversation phone as default', function () {
    $tool = new CaptureLead($this->conversation);

    $tool->handle(new Request([
        'full_name' => 'Ana López',
    ]));

    $lead = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($lead->phone)->toBe('+51999888777');
});

test('capture lead auto-detects country from phone when not provided', function () {
    $tool = new CaptureLead($this->conversation);

    $tool->handle(new Request([
        'full_name' => 'Carlos Méndez',
    ]));

    $lead = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($lead->country)->toBe('Perú');
});

test('capture lead uses provided country over auto-detected', function () {
    $tool = new CaptureLead($this->conversation);

    $tool->handle(new Request([
        'full_name' => 'Carlos Méndez',
        'country' => 'Chile',
    ]));

    $lead = Lead::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($lead->country)->toBe('Chile');
});

// --- EscalateToHuman Tests ---

test('escalate to human creates escalation record', function () {
    $tool = new EscalateToHuman($this->conversation);

    $result = $tool->handle(new Request([
        'reason' => 'customer_request',
        'note' => 'El cliente insiste en hablar con un humano',
    ]));

    $escalation = Escalation::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($escalation)->not->toBeNull();
    expect($escalation->reason)->toBe('customer_request');
    expect($escalation->note)->toBe('El cliente insiste en hablar con un humano');
    expect($escalation->tenant_id)->toBe($this->tenant->id);
    expect((string) $result)->toContain('escalated');
});

test('escalate to human changes conversation status to escalated', function () {
    $tool = new EscalateToHuman($this->conversation);

    $tool->handle(new Request([
        'reason' => 'complex_question',
    ]));

    $this->conversation->refresh();

    expect($this->conversation->status)->toBe(ConversationStatus::Escalated);
});

test('escalate to human creates escalation without optional note', function () {
    $tool = new EscalateToHuman($this->conversation);

    $tool->handle(new Request([
        'reason' => 'negative_sentiment',
    ]));

    $escalation = Escalation::withoutGlobalScopes()->where('conversation_id', $this->conversation->id)->first();

    expect($escalation->reason)->toBe('negative_sentiment');
    expect($escalation->note)->toBeNull();
});

// --- SendMedia Tests ---

test('send media dispatches whatsapp message job', function () {
    Queue::fake();

    $tool = new SendMedia($this->channel, $this->conversation);

    $result = $tool->handle(new Request([
        'type' => 'image',
        'url' => 'https://example.com/photo.jpg',
        'caption' => 'Nuestra oficina',
    ]));

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->text === "Nuestra oficina\nhttps://example.com/photo.jpg"
            && $job->conversation->id === $this->conversation->id;
    });

    expect((string) $result)->toContain('image');
});

test('send media works without caption', function () {
    Queue::fake();

    $tool = new SendMedia($this->channel, $this->conversation);

    $tool->handle(new Request([
        'type' => 'document',
        'url' => 'https://example.com/brochure.pdf',
    ]));

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->text === 'https://example.com/brochure.pdf';
    });
});

test('send media handles video type', function () {
    Queue::fake();

    $tool = new SendMedia($this->channel, $this->conversation);

    $tool->handle(new Request([
        'type' => 'video',
        'url' => 'https://example.com/demo.mp4',
        'caption' => 'Demo del producto',
    ]));

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->text === "Demo del producto\nhttps://example.com/demo.mp4";
    });
});

// --- Tool Descriptions ---

test('tools provide descriptions', function () {
    $captureLead = new CaptureLead($this->conversation);
    $escalate = new EscalateToHuman($this->conversation);
    $sendMedia = new SendMedia($this->channel, $this->conversation);

    expect((string) $captureLead->description())->not->toBeEmpty();
    expect((string) $escalate->description())->not->toBeEmpty();
    expect((string) $sendMedia->description())->not->toBeEmpty();
});
