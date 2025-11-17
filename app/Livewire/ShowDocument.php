<?php

namespace App\Livewire;

use App\Services\DocumentService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Document;
#[Layout('layouts.app')]
class ShowDocument extends Component
{
    public ?Document $document = null;

    public function mount(int $documentId){
        $this->document = app(DocumentService::class)->getDocument($documentId);
    }
    public function render()
    {
        $event = $this->document->event;
        $this->authorize('viewMyDocument', [$event]);

//        $path = app(DocumentService::class)->getDocumentPath($this->document);

        return view('livewire.show-document', [
            'document' => $this->document,
            'pdfUrl'   => route('documents.pdf', $this->document),
        ]);
    }
}
