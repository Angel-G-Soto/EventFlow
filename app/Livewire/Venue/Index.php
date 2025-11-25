<?php

/**
 * Livewire Component: Manage Venues
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Allows a Department Director to view department venues and assign/change
 * venue managers restricted to the same department.
 *
 * Responsibilities:
 * - Load venues for a department and their current managers.
 * - Provide validated reassignment of a venue's manager.
 * - Support filtering/search and pagination.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Venue;

use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
/**
 * Class Index
 *
 * Livewire component for Department Directors to manage venue managers.
 * Loads venues for a given department and supports reassigning managers.
 */
class Index extends Component
{
    use WithPagination;
/**
 * @var string
 */
    public string $paginationTheme = 'bootstrap';

//    public $venues;
    /**
     * Current search input before applying.
     */
    public string $searchDraft = '';

    /**
     * Search term currently being applied to the query.
     */
    public string $searchTerm = '';

    public string $departmentName = '';

    protected VenueService $venueService;
/**
 * Configure action.
 * @param Venue $venue
 * @return mixed
 */

    public function configure(Venue $venue){

        $this->authorize('update-availability', $venue);
        $this->authorize('update-requirements', $venue);

        $this->redirectRoute('venue.requirements.edit', ['venue'=>$venue]);
    }
/**
 * Render the Manage Venues view with venues and managers.
 * @return \Illuminate\Contracts\View\View
 */
    public function applySearch(): void
    {
        $this->searchTerm = trim($this->searchDraft);
        $this->resetPage();
    }

    public function resetSearch(): void
    {
        $this->searchDraft = '';
        $this->searchTerm = '';
        $this->resetPage();
    }

    public function boot(VenueService $venueService): void
    {
        $this->venueService = $venueService;
    }

    public function render()
    {
        $this->authorize('viewIndex', Venue::class);
        $venues = $this->venueService->getVenuesByDepartmentWithSearch(
            Auth::user()->department_id,
            $this->searchTerm
        );

        $this->departmentName = Auth::user()->department->name ?? 'Department';

        return view('livewire.venue.index', compact('venues'));
    }
}
