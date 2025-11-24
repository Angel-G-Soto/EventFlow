<?php

namespace App\Livewire\Request\Org;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class Filters extends Component
{
    public array $roles = [];

//    #[Url(as: 'role', history: true)]
    public string $selectedRole = '';

    public string $searchTitle = '';
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        // My Requests view does not filter by role; avoid loading roles.
        // $this->roles = [];
    }

    public function apply(): void
    {
        $this->dispatch('filters-changed', [
            'role' => $this->selectedRole,
            'searchTitle' => $this->searchTitle,
            'sortDirection' => $this->sortDirection,
        ]);
//        dd($this->selectedRole, $this->searchTitle, $this->sortDirection);
        $this->dispatch('filters-applied');
    }

    public function resetFilters(): void
    {
        $this->selectedRole = '';
        $this->searchTitle = '';
        $this->sortDirection = 'desc';
        $this->apply();
    }

    public function render()
    {
        return view('livewire.request.org.filters');
    }
}
