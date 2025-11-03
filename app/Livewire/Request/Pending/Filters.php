<?php

/**
 * Livewire Component: Filters
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Provides reusable multi-select filters (Organization, Category/Type, Venue)
 * and emits change events or updates bound state for parent components/pages.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Request\Pending;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Class Filters
 *
 * Livewire component exposing reusable multi-select filters.
 */
class Filters extends Component
{
    // Option lists
/**
 * @var array<int, \App\Models\Category>|\Illuminate\Support\Collection
 */
    public array $categories = [];
/**
 * @var array<int, \App\Models\Venue>|\Illuminate\Support\Collection
 */
    public array $venues = [];
/**
 * @var array
 */
    public array $orgs = []; // keep if you need it
/**
 * @var array
 */
    public array $roles = []; // keep if you need it


    // Selections (URL syncing optional)
    #[Url(as: 'categories', history: true)]
/**
 * @var array<int|string>
 */
    public array $selectedCategories = [];

    #[Url(as: 'venues', history: true)]
/**
 * @var array<int|string>
 */
    public array $selectedVenues = [];

    #[Url(as: 'orgs', history: true)]
/**
 * @var array
 */
    public array $selectedOrgs = [];

    #[Url(as: 'roles', history: true)]
/**
 * @var array
 */
    public array $selectedRoles = [];
/**
 * Initialize filter state and preload option lists.
 * @return void
 */

    public function mount(): void
    {
        $this->categories = Category::orderBy('name', 'desc')->get(['id','name'])->toArray();
        $this->venues     = Venue::orderBy('name', 'desc')->get(['id','name'])->toArray();

        $this->orgs = Event::query()
            ->whereNotNull('organization_name')
            ->distinct()
            ->orderBy('organization_name', 'asc')
            ->get(['organization_name'])
            ->toArray();
        // $this->orgs = Organization::orderBy('name')->get(['id','name'])->toArray();

        $this->roles = Auth::user()->roles()->get(['name'])->toArray();
    }
/**
 * SelectAll action.
 * @param string $which
 * @return void
 */

    public function selectAll(string $which): void
    {
        if ($which === 'categories') $this->selectedCategories = array_column($this->categories, 'id');
        if ($which === 'venues')     $this->selectedVenues     = array_column($this->venues, 'id');
        if ($which === 'orgs')       $this->selectedOrgs       = array_column($this->orgs, 'organization_name');
        if ($which === 'roles')       $this->selectedRoles       = array_column($this->roles, 'name');
        $this->apply();
    }
/**
 * Clear action.
 * @param string $which
 * @return void
 */

    public function clear(string $which): void
    {
        if ($which === 'categories') $this->selectedCategories = [];
        if ($which === 'venues')     $this->selectedVenues     = [];
        if ($which === 'orgs')       $this->selectedOrgs       = [];
        if ($which === 'roles')       $this->selectedRoles       = [];
        $this->apply();
    }
/**
 * Apply current filters and notify parent via event or URL query string.
 * @return void
 */

    public function apply(): void
    {
        // send to the list component
        $this->dispatch(
            'filters-changed',
            categories: $this->selectedCategories,
            venues: $this->selectedVenues,
            orgs: $this->selectedOrgs,
            roles: $this->selectedRoles,
        );

        // close UI (caught by JS in the Blade below)
        $this->dispatch('filters-applied');
    }
/**
 * Render the Blade view for the filters component.
 * @return \Illuminate\Contracts\View\View
 */

    public function render()
    {
        return view('livewire.request.pending.filters');
    }
}
