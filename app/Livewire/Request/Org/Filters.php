<?php

namespace App\Livewire\Request\Org;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Services\UserService;
class Filters extends Component
{
    public array $roles = [];

//    #[Url(as: 'role', history: true)]
    public string $selectedRole = '';

    public string $searchTitle = '';
    public string $sortDirection = 'desc';

    /**
     * Loads available organization roles for the authenticated user.
     *
     * @return void
     */
    public function mount(): void
    {
        // Load roles for current user
        $this->roles = app(UserService::class)->rolesOrg(Auth::user());
    }

    /**
     * Emits the current filters to the parent component and signals apply.
     *
     * @return void
     */
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

    /**
     * Resets filters to defaults and reapplies them.
     *
     * @return void
     */
    public function resetFilters(): void
    {
        $this->selectedRole = '';
        $this->searchTitle = '';
        $this->sortDirection = 'desc';
        $this->apply();
    }

    /**
     * Renders the filters view.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        return view('livewire.request.org.filters');
    }
}
