<?php

namespace App\Livewire\Request\Pending\Approver;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Filters extends Component
{
    // Option lists
    public array $categories = [];
    public array $venues = [];
    public array $orgs = []; // keep if you need it

    // Selections (URL syncing optional)
    #[Url(as: 'categories', history: true)]
    public array $selectedCategories = [];

    #[Url(as: 'venues', history: true)]
    public array $selectedVenues = [];

    #[Url(as: 'orgs', history: true)]
    public array $selectedOrgs = [];

    public function mount(): void
    {
        $this->categories = Category::orderBy('name')->get(['id','name'])->toArray();
        $this->venues     = Venue::orderBy('name')->get(['id','name'])->toArray();

        $this->orgs = Event::query()
            ->select('organization_nexo_id', DB::raw('MIN(organization_nexo_name) as organization_nexo_name'))
            ->whereNotNull('organization_nexo_id')
            ->groupBy('organization_nexo_id')          // one row per org id
            ->orderBy('organization_nexo_name')
            ->get()
            ->toArray();
        // $this->orgs = Organization::orderBy('name')->get(['id','name'])->toArray();
    }

    public function selectAll(string $which): void
    {
        if ($which === 'categories') $this->selectedCategories = array_column($this->categories, 'id');
        if ($which === 'venues')     $this->selectedVenues     = array_column($this->venues, 'id');
        if ($which === 'orgs')       $this->selectedOrgs       = array_column($this->orgs, 'organization_nexo_id');
        $this->apply();
    }

    public function clear(string $which): void
    {
        if ($which === 'categories') $this->selectedCategories = [];
        if ($which === 'venues')     $this->selectedVenues     = [];
        if ($which === 'orgs')       $this->selectedOrgs       = [];
        $this->apply();
    }

    public function apply(): void
    {
        // send to the list component
        $this->dispatch(
            'filters-changed',
            categories: $this->selectedCategories,
            venues: $this->selectedVenues,
            orgs: $this->selectedOrgs
        );

        // close UI (caught by JS in the Blade below)
        $this->dispatch('filters-applied');
    }

    public function render()
    {
        return view('livewire.request.pending.approver.filters');
    }
}
