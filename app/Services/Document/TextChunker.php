<?php

namespace App\Services\Document;

class TextChunker
{
    /**
     * Split text into overlapping chunks.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<int, array{content: string, token_count: int, metadata: array<string, mixed>}>
     */
    public function chunk(string $text, array $metadata = [], int $targetTokens = 500, int $overlapTokens = 50): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\n\s*\n/', $text);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        if (empty($paragraphs)) {
            return [];
        }

        $pageTexts = $metadata['page_texts'] ?? [];
        $chunks = [];
        $currentContent = '';
        $currentTokens = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraphTokens = $this->estimateTokenCount($paragraph);

            if ($currentTokens + $paragraphTokens > $targetTokens && $currentContent !== '') {
                $chunkMetadata = $this->resolveChunkMetadata($currentContent, $pageTexts, $metadata);
                $chunks[] = [
                    'content' => trim($currentContent),
                    'token_count' => $currentTokens,
                    'metadata' => $chunkMetadata,
                ];

                $overlapContent = $this->extractOverlap($currentContent, $overlapTokens);
                $currentContent = $overlapContent !== '' ? $overlapContent."\n\n".$paragraph : $paragraph;
                $currentTokens = $this->estimateTokenCount($currentContent);
            } else {
                $currentContent .= ($currentContent !== '' ? "\n\n" : '').$paragraph;
                $currentTokens += $paragraphTokens;
            }
        }

        if (trim($currentContent) !== '') {
            $chunkMetadata = $this->resolveChunkMetadata($currentContent, $pageTexts, $metadata);
            $chunks[] = [
                'content' => trim($currentContent),
                'token_count' => $this->estimateTokenCount($currentContent),
                'metadata' => $chunkMetadata,
            ];
        }

        return $chunks;
    }

    public function estimateTokenCount(string $text): int
    {
        $wordCount = str_word_count($text);

        return (int) ceil($wordCount / 0.75);
    }

    /**
     * Extract the last N tokens worth of text for overlap.
     */
    private function extractOverlap(string $text, int $overlapTokens): string
    {
        $words = preg_split('/\s+/', trim($text));
        $overlapWords = (int) ceil($overlapTokens * 0.75);

        if (count($words) <= $overlapWords) {
            return $text;
        }

        return implode(' ', array_slice($words, -$overlapWords));
    }

    /**
     * Resolve metadata for a chunk, including page information from PDF.
     *
     * @param  array<int, array{page: int, text: string}>  $pageTexts
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function resolveChunkMetadata(string $content, array $pageTexts, array $metadata): array
    {
        $chunkMeta = [];

        if (! empty($pageTexts)) {
            $pages = [];
            foreach ($pageTexts as $pageInfo) {
                if (str_contains($content, substr($pageInfo['text'], 0, 100))) {
                    $pages[] = $pageInfo['page'];
                }
            }

            if (! empty($pages)) {
                $chunkMeta['pages'] = $pages;
            }
        }

        return $chunkMeta;
    }
}
