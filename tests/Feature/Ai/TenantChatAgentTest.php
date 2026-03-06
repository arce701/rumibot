<?php

use App\Ai\Agents\TenantChatAgent;
use App\Ai\Middleware\TrackTokenUsage;
use App\Ai\Tools\CaptureLead;
use App\Ai\Tools\EscalateToHuman;
use App\Ai\Tools\SendMedia;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'system_prompt' => 'Eres el asistente de RumiStar, una empresa de software.',
    ]);
    $this->salesChannel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->supportChannel = Channel::factory()->support()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->salesChannel->id,
    ]);
});

test('agent composes instructions from base prompt, tenant prompt, and channel override', function () {
    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);

    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain(config('rumibot.base_prompt'))
        ->toContain('RumiStar')
        ->toContain($this->salesChannel->system_prompt_override);
});

test('agent instructions excludes null layers', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'system_prompt_override' => null,
    ]);

    $agent = new TenantChatAgent($this->tenant, $channel, $this->conversation);

    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain(config('rumibot.base_prompt'))
        ->toContain('RumiStar')
        ->not->toContain("\n\n\n\n"); // no double blank lines from null
});

test('agent loads conversation messages in correct order', function () {
    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'tenant_id' => $this->tenant->id,
        'role' => 'user',
        'content' => 'Hola, primer mensaje',
    ]);

    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'tenant_id' => $this->tenant->id,
        'role' => 'assistant',
        'content' => 'Hola, respuesta del bot',
    ]);

    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'tenant_id' => $this->tenant->id,
        'role' => 'user',
        'content' => 'Segundo mensaje',
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);
    $messages = $agent->messages();

    expect($messages)->toHaveCount(3);
    expect($messages[0]->content)->toBe('Hola, primer mensaje');
    expect($messages[1]->content)->toBe('Hola, respuesta del bot');
    expect($messages[2]->content)->toBe('Segundo mensaje');
});

test('agent respects max conversation messages limit', function () {
    $this->tenant->update(['ai_context_window' => 2]);

    Message::factory()->count(5)->create([
        'conversation_id' => $this->conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);
    $messages = $agent->messages();

    expect($messages)->toHaveCount(2);
});

test('sales channel agent includes CaptureLead tool', function () {
    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);
    $tools = $agent->tools();

    $toolClasses = array_map(fn ($tool) => $tool::class, is_array($tools) ? $tools : iterator_to_array($tools));

    expect($toolClasses)
        ->toContain(SendMedia::class)
        ->toContain(EscalateToHuman::class)
        ->toContain(CaptureLead::class);
});

test('support channel agent does not include CaptureLead tool', function () {
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->supportChannel->id,
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->supportChannel, $conversation);
    $tools = $agent->tools();

    $toolClasses = array_map(fn ($tool) => $tool::class, is_array($tools) ? $tools : iterator_to_array($tools));

    expect($toolClasses)
        ->toContain(SendMedia::class)
        ->toContain(EscalateToHuman::class)
        ->not->toContain(CaptureLead::class);
});

test('agent includes TrackTokenUsage middleware', function () {
    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);
    $middleware = $agent->middleware();

    expect($middleware)->toHaveCount(1);
    expect($middleware[0])->toBeInstanceOf(TrackTokenUsage::class);
});

test('agent can be faked and returns fake response', function () {
    TenantChatAgent::fake(['Hola, soy el bot de RumiStar.']);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);

    $response = $agent->prompt('Hola');

    expect((string) $response)->toBe('Hola, soy el bot de RumiStar.');
});

test('agent assertPrompted verifies prompt was sent', function () {
    TenantChatAgent::fake(['Respuesta fake']);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);
    $agent->prompt('Quiero comprar un producto');

    TenantChatAgent::assertPrompted(fn ($prompt) => $prompt->prompt === 'Quiero comprar un producto');
});

test('agent assertNeverPrompted passes when no prompts sent', function () {
    TenantChatAgent::fake();

    TenantChatAgent::assertNeverPrompted();
});

test('agent instructions include country context when conversation has contact_country', function () {
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->salesChannel->id,
        'contact_phone' => '50234850199',
        'contact_country' => 'GT',
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $conversation);

    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain('Guatemala')
        ->toContain('+502 3485 0199')
        ->toContain('no lo preguntes');
});

test('agent instructions detect country from phone when contact_country is null', function () {
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->salesChannel->id,
        'contact_phone' => '51999888777',
        'contact_country' => null,
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $conversation);

    $instructions = $agent->instructions();

    expect($instructions)->toContain('Perú');
});

test('agent fake returns sequential responses', function () {
    TenantChatAgent::fake([
        'Primera respuesta',
        'Segunda respuesta',
    ]);

    $agent = new TenantChatAgent($this->tenant, $this->salesChannel, $this->conversation);

    $first = $agent->prompt('Primer mensaje');
    $second = $agent->prompt('Segundo mensaje');

    expect((string) $first)->toBe('Primera respuesta');
    expect((string) $second)->toBe('Segunda respuesta');
});
