<?php

/**
 * Livewire Component: Filters
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Provides reusable multi-select filters (Organization, Category/Type, Venue)
 * and emits change events or updates bound state for parent components/pages.
 *
 * Responsibilities:
 * - Hold UI state for filter selections and search terms.
 * - Sanitize/serialize filter values (CSV/array) for queries.
 * - Notify parent components via events or URL query strings.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Request\Org;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Class Filters
 *
 * Reusable Livewire filters component. Intended to sit above index lists
 * (e.g., Events index) and dispatch updates when selections change.
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
    public array $orgs = [];
/**
 * @var array
 */
    public array $statusOptions = [
        'pending approval - advisor',
        'pending approval - manager',
        'pending approval - event approver',
        'pending approval - deanship of administration',
        'approved',
        'rejected',
        'cancelled',
        'withdrawn',
        'completed',
    ];

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
    #[Url(as: 'orgs', history: true)]
/**
 * @var array
 */
    public array $selectedStatuses = [];
/**
 * Initialize filter state and preload option lists.
 * @return void
 */

    public function mount(): void
    {
        $this->categories = Category::orderBy('name')->get(['id','name'])->toArray();
        $this->venues     = Venue::orderBy('name')->get(['id','name'])->toArray();

        $this->orgs = Event::query()
            ->select('organization_name')//, DB::raw('MIN(organization_nexo_name) as organization_nexo_name'))
            ->whereNotNull('organization_name')
            //->groupBy('organization_nexo_id')          // one row per org id
            ->orderBy('organization_name')
            ->get()
            ->toArray();
        // $this->orgs = Organization::orderBy('name')->get(['id','name'])->toArray();
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
        if ($which === 'statuses')   $this->selectedStatuses   = $this->statusOptions;
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
            orgs: $this->selectedOrgs
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
        return view('livewire.request.org.filters');
    }
}
