<?php

namespace App\Livewire\Request;

use App\Models\Event;
use Livewire\Component;

class Details extends Component
{
    public function render()
    {
        return view('livewire.request.details')->layout('layouts.public');
    }
}
