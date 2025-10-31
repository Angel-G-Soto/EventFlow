<?php

namespace App\Livewire\Request\Pending\Approver;

use App\Models\Event;
use Livewire\Component;

class Details extends Component
{
    public Event $event;
    public string $justification = '';
    public function getIsReadyProperty(): bool
    {
        return strlen(trim($this->justification)) >= 10;
    }

    public function save()
    {
        $this->validate(['justification' => 'required|min:10']);
        // ... do your action
        $this->redirectRoute('approver.index');
    }

    public function approve()
    {
        // ... do your action
        $this->redirectRoute('approver.index');
    }

    public function back()
    {
        $this->redirectRoute('approver.pending.index');
    }


    public function render()
    {
        $docs = [
            ['title' => 'Syllabus', 'url' => asset('23382.pdf'), 'description' => 'Fall 2025'],
            ['title' => 'Reglamento interno', 'url' => asset('REGLAMENTO-INTERNO.pdf'), 'description' => 'Fall 2025']
        ];
        return view('livewire.request.pending.approver.details', compact('docs'));
    }
}
