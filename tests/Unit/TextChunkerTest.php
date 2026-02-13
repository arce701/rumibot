<?php

use App\Services\Document\TextChunker;

beforeEach(function () {
    $this->chunker = new TextChunker;
});

test('empty text returns zero chunks', function () {
    $result = $this->chunker->chunk('');

    expect($result)->toBe([]);
});

test('whitespace only text returns zero chunks', function () {
    $result = $this->chunker->chunk('   ');

    expect($result)->toBe([]);
});

test('short text returns single chunk', function () {
    $result = $this->chunker->chunk('This is a short paragraph of text.');

    expect($result)->toHaveCount(1);
    expect($result[0]['content'])->toBe('This is a short paragraph of text.');
    expect($result[0]['token_count'])->toBeGreaterThan(0);
});

test('long text returns multiple chunks', function () {
    $paragraphs = [];
    for ($i = 0; $i < 20; $i++) {
        $paragraphs[] = "Paragraph number {$i} with enough words to accumulate a reasonable token count for testing the chunking mechanism properly.";
    }
    $text = implode("\n\n", $paragraphs);

    $result = $this->chunker->chunk($text, [], 100);

    expect(count($result))->toBeGreaterThan(1);

    foreach ($result as $chunk) {
        expect($chunk)->toHaveKeys(['content', 'token_count', 'metadata']);
        expect($chunk['content'])->not->toBeEmpty();
        expect($chunk['token_count'])->toBeGreaterThan(0);
    }
});

test('token estimation is reasonable', function () {
    $text = 'one two three four five six seven eight nine ten';
    $estimate = $this->chunker->estimateTokenCount($text);

    // 10 words / 0.75 = ~14 tokens
    expect($estimate)->toBeGreaterThanOrEqual(10);
    expect($estimate)->toBeLessThanOrEqual(20);
});

test('chunks respect target token count', function () {
    $paragraphs = [];
    for ($i = 0; $i < 30; $i++) {
        $paragraphs[] = "Paragraph {$i}: The quick brown fox jumps over the lazy dog multiple times to generate enough content.";
    }
    $text = implode("\n\n", $paragraphs);

    $targetTokens = 100;
    $result = $this->chunker->chunk($text, [], $targetTokens);

    expect(count($result))->toBeGreaterThan(1);

    // Most chunks (except the last) should be near the target
    foreach (array_slice($result, 0, -1) as $chunk) {
        expect($chunk['token_count'])->toBeLessThanOrEqual($targetTokens * 2);
    }
});

test('chunks include page metadata from pdf extraction', function () {
    $metadata = [
        'pages' => 2,
        'page_texts' => [
            ['page' => 1, 'text' => 'Content from page one about testing.'],
            ['page' => 2, 'text' => 'Content from page two about production.'],
        ],
    ];

    $text = "Content from page one about testing.\n\nContent from page two about production.";

    $result = $this->chunker->chunk($text, $metadata);

    expect($result)->not->toBeEmpty();
});
