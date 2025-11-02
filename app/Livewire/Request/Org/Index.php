<?php

/**
 * Livewire Component: Index
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Displays a paginated, filterable list (e.g., events/requests) for users.
 *
 * Responsibilities:
 * - Hold UI state (filters, pagination) and synchronize with URL if needed.
 * - Build Eloquent queries based on selected filters and search terms.
 * - Render the Blade view with the resulting dataset.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Request\Org;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Class Index
 *
 * Livewire index/list component with optional multi-filters and pagination.
 * Suitable for listing Events or Requests and reacting to filter changes.
 */
#[Layout('layouts.user')]

class Index extends Component
{

    use WithPagination;
/**
 * @var string
 */
    public string $paginationTheme = 'bootstrap';
/**
 * @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>}
 */
    public array $filters = [
        'categories' => [],
        'venues'     => [],
        'orgs'       => []
    ];

    #[On('filters-changed')]
/**
 * OnFiltersChanged action.
 *
 * Listens for Livewire events: 'filters-changed'.
 * @param array $categories
 * @param array $venues
 * @param array $orgs
 * @param array $statuses
 * @return void
 */
    public function onFiltersChanged(array $categories = [], array $venues = [], array $orgs = [], array $statuses = []): void
    {
        $this->filters['categories'] = array_map('intval', $categories);
        $this->filters['venues']     = array_map('intval', $venues);
        $this->filters['orgs']       = array_map('intval', $orgs);
        $this->resetPage(); // go back to page 1 after changing filters
    }
/**
 * Build the query, paginate results, and render the view.
 *
 * Listens for Livewire events: 'filters-changed'.
 * @return \Illuminate\Contracts\View\View
 */

    public function render()
    {
        $q = Event::query()->with(['venue','categories'])->where('creator_id', auth()->id());

        // If your Event has a SINGLE category_id column, use this:
//        if (!empty($this->filters['categories'])) {
//            $q->whereIn('category_id', $this->filters['categories']);
//        }

        // If your Event has MANY categories (pivot), replace the block above with:
        if (!empty($this->filters['categories'])) {
            $ids = $this->filters['categories'];
            $q->whereHas('categories', fn($qq) => $qq->whereIn('categories.id', $ids));
        }

        if (!empty($this->filters['venues'])) {
            $q->whereIn('venue_id', $this->filters['venues']);
        }

        if (!empty($this->filters['orgs'])) {
            $q->whereIn('organization_name', $this->filters['orgs']);
        }

        $events = $q->orderByDesc('created_at')->paginate(8);

        return view('livewire.request.org.index', compact('events'));
    }
}
