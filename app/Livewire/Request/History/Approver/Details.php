<?php

namespace App\Livewire\Request\History\Approver;

use Livewire\Component;

use App\Models\Event;

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
        $this->redirectRoute('approver.history.index');
    }

    public function approve()
    {
        // ... do your action
        $this->redirectRoute('approver.history.index');
    }

    public function back()
    {
        $this->redirectRoute('approver.history.index');
    }


    public function render()
    {
        $docs = [
            ['title' => 'Syllabus', 'url' => asset('23382.pdf'), 'description' => 'Fall 2025'],
            ['title' => 'Reglamento interno', 'url' => asset('REGLAMENTO-INTERNO.pdf'), 'description' => 'Fall 2025']
        ];
        return view('livewire.request.history.approver.details', compact('docs'));
    }
}
