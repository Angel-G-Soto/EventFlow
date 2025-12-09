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

use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
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
#[Layout('layouts.app')]

class Index extends Component
{
    use WithPagination;

    public string $paginationTheme = 'bootstrap';

    // Filters applied from the Filters component
    public array $filters = [
        'role' => '',
        'searchTitle' => '',
        'sortDirection' => 'desc',
    ];

    /**
     * Receives filter updates from the Filters component and resets pagination.
     *
     * @param array{role?: string, searchTitle?: string, sortDirection?: string} $filters
     * @return void
     */
    #[On('filters-changed')]
    public function onFiltersChanged(array $filters): void
    {
        // Map dispatched keys to local filters
        $this->filters['role'] = $filters['role'] ?? '';
        $this->filters['searchTitle'] = $filters['searchTitle'] ?? '';
        $this->filters['sortDirection'] = $filters['sortDirection'] ?? 'desc';

        $this->resetPage(); // reset pagination
    }
/**
 * Build the query, paginate results, and render the view.
 *
 * Listens for Livewire events: 'filters-changed'.
 * @return \Illuminate\Contracts\View\View
 */

    /**
     * Builds the filtered request list and renders the view.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        $q = app(EventService::class)->getMyRequestedEvents(Auth::user());

        // Apply search filter
        if (!empty($this->filters['searchTitle'])) {
            $q->where(function($query) {
                $query->where('title', 'like', '%'.$this->filters['searchTitle'].'%')
                    ->orWhere('organization_name', 'like', '%'.$this->filters['searchTitle'].'%');
            });
        }


        // Apply sort direction
        $sortDir = in_array($this->filters['sortDirection'], ['asc','desc']) ? $this->filters['sortDirection'] : 'desc';
        $q->orderBy('created_at', $sortDir);

        $events = $q->paginate(8);

        return view('livewire.request.org.index', compact('events'));
    }
}
