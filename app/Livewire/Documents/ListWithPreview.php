<?php

// app/Livewire/Documents/ListWithPreview.php
namespace App\Livewire\Documents;

use Livewire\Component;

class ListWithPreview extends Component
{
    public array $docs = []; // each: ['title' => '', 'url' => '', 'description' => '']

    public ?string $previewUrl = null;
    public ?string $previewTitle = null;

    public function preview(string $url, string $title): void
    {
        $this->previewUrl = $url;
        $this->previewTitle = $title;
        $this->dispatch('open-modal', id: 'pdfModal');
    }

    public function render()
    {
        return view('livewire.documents.list-with-preview');
    }
}
