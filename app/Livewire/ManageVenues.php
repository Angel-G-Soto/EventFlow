<?php

namespace App\Livewire;

use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[layout('layouts.public')]
class ManageVenues extends Component
{
    use WithPagination;
    public string $paginationTheme = 'bootstrap';

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
