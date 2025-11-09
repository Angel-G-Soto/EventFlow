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
 * @var string
 */
    public string $search = '';
/**
 * Configure action.
 * @param Venue $venue
 * @return mixed
 */

    public function configure(Venue $venue){

        $this->redirectRoute('venue.requirements.edit', ['venue'=>$venue]);
    }
/**
 * Render the Manage Venues view with venues and managers.
 * @return \Illuminate\Contracts\View\View
 */
    public function render()
    {
        $venues = Venue::query()->where('department_id', Auth::user()->department_id)
            ->latest()
            ->paginate(8);
//        dd($venues);
        return view('livewire.venue.index', compact('venues'));
    }
}
