<?php

namespace App\Livewire;

use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Component;
#[layout('layouts.public')]
class ManageVenues extends Component
{

//    public $venues;
    public string $search = '';
    public function render()
    {
        $venues = Venue::query()
            ->latest()
            ->paginate(5);
        return view('livewire.manage-venues', compact('venues'));
    }
}
