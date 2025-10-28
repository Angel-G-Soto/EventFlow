<?php

namespace App\Livewire\Request\Pending\org;

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
    }

    public function render()
    {
        return view('livewire.request.pending.org.details')->layout('layouts.public');
    }
}
