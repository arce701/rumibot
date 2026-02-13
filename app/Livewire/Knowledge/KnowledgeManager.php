<?php

namespace App\Livewire\Knowledge;

use App\Jobs\ProcessDocument;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Knowledge Base')]
class KnowledgeManager extends Component
{
    use AuthorizesRequests, WithFileUploads;

    #[Validate('required|string|max:200')]
    public string $title = '';

    #[Validate('required|file|mimes:pdf,txt,md,csv|max:10240')]
    public $file;

    /** @var string[] */
    public array $channelScope = [];

    public bool $showUploadForm = false;

    public function upload(): void
    {
        $this->authorize('knowledge.upload');
        $this->validate();

        $tenant = auth()->user()->currentTenant;

        $path = $this->file->store(
            "tenants/{$tenant->id}/documents",
            's3'
        );

        $document = KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'title' => $this->title,
            'file_name' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $this->file->getSize(),
            'mime_type' => $this->file->getMimeType(),
            'channel_scope' => $this->channelScope,
        ]);

        ProcessDocument::dispatch($document);

        $this->reset(['title', 'file', 'channelScope', 'showUploadForm']);

        session()->flash('message', __('Document uploaded and queued for processing.'));
    }

    public function reprocess(string $documentId): void
    {
        $document = KnowledgeDocument::findOrFail($documentId);

        if (! $document->isProcessable()) {
            return;
        }

        $document->update([
            'status' => DocumentStatus::Pending,
            'error_message' => null,
        ]);

        ProcessDocument::dispatch($document);
    }

    public function deleteDocument(string $documentId): void
    {
        $this->authorize('knowledge.delete');
        $document = KnowledgeDocument::findOrFail($documentId);
        $document->delete();
    }

    public function render(): \Illuminate\View\View
    {
        $tenant = auth()->user()->currentTenant;

        $documents = KnowledgeDocument::where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        $channels = $tenant->channels()->where('is_active', true)->get();

        $hasProcessing = $documents->contains(fn ($doc) => $doc->status === DocumentStatus::Processing);

        return view('livewire.knowledge.knowledge-manager', [
            'documents' => $documents,
            'channels' => $channels,
            'hasProcessing' => $hasProcessing,
        ]);
    }
}
