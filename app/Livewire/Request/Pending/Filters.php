<?php

namespace App\Livewire\Request\Pending;


use App\Services\UserService;
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
        // Load roles for current user
        $this->roles = app(UserService::class)->rolesPending(Auth::user());
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
        return view('livewire.request.pending.filters');
    }
}
