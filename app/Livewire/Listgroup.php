<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.public')]
class Listgroup extends Component
{
    use WithPagination;
//    public $events = [];
//    public function mount($array = [])
//    {
//        $this->events = $array;
//    }
    public function render()
    {
        $events = Event::query()
            ->oldest('created_at')
            ->paginate(8);
        ;
        return view('livewire.listgroup', compact('events'));
    }
}
