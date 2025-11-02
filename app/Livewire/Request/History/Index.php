<?php


/**
 * Livewire Request History Component.
*
* This file belongs to the EventFlow project (Laravel 12 + Livewire 3 + Bootstrap 5).
* It renders a paginated list of event requests with multi-filter support.
*
* Responsibilities:
* - Hold UI state for filters and pagination.
* - Build an eloquent query based on selected filters.
* - Return the view for the Request History page.
*
* @author  EventFlow Team
* @license MIT
* @since   2025-11-01
*/

namespace App\Livewire\Request\History;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\Event;
use App\Services\EventService;
use Livewire\Attributes\Layout;
#[Layout('layouts.user')]
/**
 * Class Index
 *
 * Livewire component responsible for displaying a user's request history.
 * It supports filtering by categories, venues, and organizations and paginates
 * the results using Bootstrap 5 pagination styles.
 *
 * @package App\Livewire\Request\History
 */
class Index extends Component
{
    use WithPagination;
    /**
     * Pagination theme used by Livewire paginator.
     *
     * @var string
     */
    public string $paginationTheme = 'bootstrap';
    /**
     * Filter state for the list.
     *
     * Structure:
     *  - categories: int[]|string[] Selected category IDs
     *  - venues: int[]|string[]     Selected venue IDs
     *  - orgs: int[]|string[]       Selected organization IDs
     *
     * @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>}
     */
    public array $filters = [
        'categories' => [],
        'venues'     => [],
        'orgs'       => [],
    ];

    #[On('filters-changed')]
/**
 * OnFiltersChanged.
 *
 * Listens for Livewire events: 'filters-changed'.
 * @param array $categories
 * @param array $venues
 * @param array $orgs
 * @return void
 */
    public function onFiltersChanged(array $categories = [], array $venues = [], array $orgs = []): void
    {
        $this->filters['categories'] = array_map('intval', $categories);
        $this->filters['venues']     = array_map('intval', $venues);
        $this->filters['orgs']       = array_map('intval', $orgs);

        $this->resetPage(); // go back to page 1 after changing filters
    }

    /**
     * Build the events query based on current filters and return the view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
//         $q = Event::query()->with(['venue','categories']);

//         // If your Event has a SINGLE category_id column, use this:
// //        if (!empty($this->filters['categories'])) {
// //            $q->whereIn('category_id', $this->filters['categories']);
// //        }

//         // If your Event has MANY categories (pivot), replace the block above with:
//         if (!empty($this->filters['categories'])) {
//             $ids = $this->filters['categories'];
//             $q->whereHas('categories', fn($qq) => $qq->whereIn('categories.id', $ids));
//         }

//         if (!empty($this->filters['venues'])) {
//             $q->whereIn('venue_id', $this->filters['venues']);
//         }

//         if (!empty($this->filters['orgs'])) {
//             $q->whereIn('organization_nexo_id', $this->filters['orgs']);
//         }

//         $events = $q->orderByDesc('created_at')->paginate(8);

        $events = app(EventService::class)->getApproverRequestHistory(Auth::user(),
            [
                'venue_id' => $this->filters['venues'],
                'category_id' => $this->filters['categories'],
                'organization_name' => $this->filters['orgs']
            ]
        );
        //dd($events);

        return view('livewire.request.history.index', compact('events'));
    }
}
