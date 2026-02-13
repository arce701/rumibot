<?php

use App\Ai\Tools\EscalateToHuman;
use App\Events\EscalationTriggered;
use App\Listeners\SendEscalationNotification;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Escalation;
use App\Models\Tenant;
use App\Services\Discord\DiscordNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'contact_name' => 'Juan Pérez',
    ]);
});

test('EscalateToHuman tool dispatches EscalationTriggered event', function () {
    Event::fake([EscalationTriggered::class]);

    $tool = new EscalateToHuman($this->conversation);

    $tool->handle(new Request([
        'reason' => 'customer_request',
        'note' => 'Cliente quiere hablar con humano',
    ]));

    Event::assertDispatched(EscalationTriggered::class, function (EscalationTriggered $event) {
        return $event->escalation->reason === 'customer_request'
            && $event->escalation->conversation->id === $this->conversation->id;
    });
});

test('listener sends HTTP POST to Discord webhook URL', function () {
    Http::fake();

    $this->tenant->update(['settings' => ['discord_webhook_url' => 'https://discord.com/api/webhooks/test']]);

    $escalation = Escalation::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'reason' => 'customer_request',
        'note' => 'Necesita atención',
    ]);

    $escalation->load('conversation.channel', 'conversation.tenant');

    $listener = app(SendEscalationNotification::class);
    $listener->handle(new EscalationTriggered($escalation));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://discord.com/api/webhooks/test');
});

test('no HTTP request is sent when no webhook URL is configured', function () {
    Http::fake();

    $this->tenant->update(['settings' => []]);
    config(['services.discord.webhook_url' => null]);

    $escalation = Escalation::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'reason' => 'complex_question',
    ]);

    $escalation->load('conversation.channel', 'conversation.tenant');

    $listener = app(SendEscalationNotification::class);
    $listener->handle(new EscalationTriggered($escalation));

    Http::assertNothingSent();
});

test('Discord payload has correct embed format', function () {
    Http::fake();

    $this->tenant->update(['settings' => ['discord_webhook_url' => 'https://discord.com/api/webhooks/test']]);

    $escalation = Escalation::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'reason' => 'negative_sentiment',
        'note' => 'Cliente frustrado',
    ]);

    $escalation->load('conversation.channel', 'conversation.tenant');

    $notifier = new DiscordNotifier;
    $notifier->sendEscalationEmbed($escalation, 'https://discord.com/api/webhooks/test');

    Http::assertSent(function ($request) {
        $body = $request->data();
        $embed = $body['embeds'][0] ?? null;

        return $embed
            && $embed['title'] === 'Nueva Escalación'
            && $embed['color'] === 15548997
            && collect($embed['fields'])->contains('name', 'Contacto')
            && collect($embed['fields'])->contains('name', 'Canal')
            && collect($embed['fields'])->contains('name', 'Razón')
            && collect($embed['fields'])->contains('name', 'Nota');
    });
});

test('listener implements ShouldQueue', function () {
    expect(SendEscalationNotification::class)->toImplement(ShouldQueue::class);
});

test('event contains escalation with loaded relations', function () {
    Event::fake([EscalationTriggered::class]);

    $tool = new EscalateToHuman($this->conversation);

    $tool->handle(new Request([
        'reason' => 'sensitive_action',
    ]));

    Event::assertDispatched(EscalationTriggered::class, function (EscalationTriggered $event) {
        return $event->escalation->relationLoaded('conversation')
            && $event->escalation->conversation->relationLoaded('channel')
            && $event->escalation->conversation->relationLoaded('tenant');
    });
});

test('falls back to global config when tenant has no discord webhook', function () {
    Http::fake();

    $this->tenant->update(['settings' => []]);
    config(['services.discord.webhook_url' => 'https://discord.com/api/webhooks/global']);

    $escalation = Escalation::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $this->conversation->id,
        'reason' => 'outside_knowledge',
    ]);

    $escalation->load('conversation.channel', 'conversation.tenant');

    $listener = app(SendEscalationNotification::class);
    $listener->handle(new EscalationTriggered($escalation));

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.com/api/webhooks/global');
});
