<?php

use App\Ai\Agents\PlaygroundChatAgent;
use App\Models\Channel;
use App\Models\Tenant;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Tools\SimilaritySearch;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'system_prompt' => 'Eres el asistente de RumiStar, una empresa de software.',
    ]);
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
});

test('agent composes instructions from base prompt, tenant prompt, and channel override', function () {
    $agent = new PlaygroundChatAgent($this->tenant, $this->channel);

    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain(config('rumibot.base_prompt'))
        ->toContain('RumiStar')
        ->toContain($this->channel->system_prompt_override);
});

test('agent instructions excludes null layers', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'system_prompt_override' => null,
    ]);

    $agent = new PlaygroundChatAgent($this->tenant, $channel);

    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain(config('rumibot.base_prompt'))
        ->toContain('RumiStar')
        ->not->toContain("\n\n\n\n");
});

test('agent returns in-memory messages', function () {
    $messages = [
        ['role' => 'user', 'content' => 'Hola'],
        ['role' => 'assistant', 'content' => 'Hola, soy el bot de RumiStar.'],
        ['role' => 'user', 'content' => 'Quiero info sobre iTrade'],
    ];

    $agent = new PlaygroundChatAgent($this->tenant, $this->channel, $messages);
    $result = $agent->messages();

    expect($result)->toHaveCount(3);
    expect($result[0]->content)->toBe('Hola');
    expect($result[1]->content)->toBe('Hola, soy el bot de RumiStar.');
    expect($result[2]->content)->toBe('Quiero info sobre iTrade');
});

test('agent returns empty array when no messages provided', function () {
    $agent = new PlaygroundChatAgent($this->tenant, $this->channel);
    $result = $agent->messages();

    expect($result)->toBeEmpty();
});

test('agent only includes SimilaritySearch tool', function () {
    $agent = new PlaygroundChatAgent($this->tenant, $this->channel);
    $tools = $agent->tools();

    $toolClasses = array_map(
        fn ($tool) => $tool::class,
        is_array($tools) ? $tools : iterator_to_array($tools),
    );

    expect($toolClasses)->toHaveCount(1);
    expect($toolClasses[0])->toBe(SimilaritySearch::class);
});

test('agent does not implement HasMiddleware', function () {
    $agent = new PlaygroundChatAgent($this->tenant, $this->channel);

    expect($agent)->not->toBeInstanceOf(HasMiddleware::class);
});

test('agent can be faked and returns fake response', function () {
    PlaygroundChatAgent::fake(['Hola, soy el bot de prueba.']);

    $agent = new PlaygroundChatAgent($this->tenant, $this->channel);
    $response = $agent->prompt('Hola');

    expect((string) $response)->toBe('Hola, soy el bot de prueba.');
});
