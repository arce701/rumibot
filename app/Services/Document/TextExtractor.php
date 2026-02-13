<?php

namespace App\Services\Document;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class TextExtractor
{
    public function __construct(
        private PdfParser $pdfParser,
    ) {}

    /**
     * Extract text content from a file.
     *
     * @return array{text: string, metadata: array<string, mixed>}
     */
    public function extract(string $filePath, string $mimeType): array
    {
        $content = Storage::disk('s3')->get($filePath);

        if ($content === null) {
            throw new RuntimeException("File not found: {$filePath}");
        }

        return match (true) {
            $mimeType === 'application/pdf' => $this->extractFromPdf($content),
            in_array($mimeType, ['text/plain', 'text/markdown', 'text/csv']) => $this->extractFromText($content),
            default => throw new RuntimeException("Unsupported mime type: {$mimeType}"),
        };
    }

    /**
     * @return array{text: string, metadata: array<string, mixed>}
     */
    private function extractFromPdf(string $content): array
    {
        $pdf = $this->pdfParser->parseContent($content);
        $pages = $pdf->getPages();
        $text = '';
        $pageTexts = [];

        foreach ($pages as $index => $page) {
            $pageText = $page->getText();
            $pageTexts[] = [
                'page' => $index + 1,
                'text' => $pageText,
            ];
            $text .= $pageText."\n\n";
        }

        $details = $pdf->getDetails();

        return [
            'text' => trim($text),
            'metadata' => [
                'pages' => count($pages),
                'page_texts' => $pageTexts,
                'title' => $details['Title'] ?? null,
                'author' => $details['Author'] ?? null,
            ],
        ];
    }

    /**
     * @return array{text: string, metadata: array<string, mixed>}
     */
    private function extractFromText(string $content): array
    {
        return [
            'text' => trim($content),
            'metadata' => [],
        ];
    }
}
