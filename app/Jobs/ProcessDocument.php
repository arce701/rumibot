<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\Document\DocumentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    public function __construct(
        public KnowledgeDocument $document,
    ) {
        $this->onQueue('low');
    }

    public function handle(DocumentProcessor $processor): void
    {
        Context::add('tenant_id', $this->document->tenant_id);

        $processor->process($this->document);
    }
}
