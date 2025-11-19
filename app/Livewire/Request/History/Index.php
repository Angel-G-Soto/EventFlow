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

use App\Models\EventHistory;
use App\Services\EventHistoryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $paginationTheme = 'bootstrap';

    // Track selected filters
    public array $filters = [
        'action'      => '',
        'searchTitle'=> '',
        'sortDirection' => 'desc',
    ];

    #[On('filters-changed')]
    public function onFiltersChanged(string $action = '', string $searchTitle = '', string $sortDirection = 'desc'): void
    {

//        dd($action, $searchTitle, $sortDirection);

        $this->filters['action'] = $action;
        $this->filters['searchTitle'] = $searchTitle;
        $this->filters['sortDirection'] = $sortDirection;
        $this->resetPage(); // reset pagination when filters change
    }

    public function render()
    {
        $this->authorize('viewMyApprovalHistory', EventHistory::class);

        $user = Auth::user();

        // Get the action filter (from Filters component)
        $action = $this->filters['action'] ?? '';

        // Start query using the service method

        $query = app(EventHistoryService::class)->genericApproverRequestHistoryV2($user);

        if (!empty($action)) {
            $query->where('action', $action);
        }

        // Apply search by title if provided
        if (!empty($this->filters['searchTitle'])) {
            $term = '%' . $this->filters['searchTitle'] . '%';
            $query->whereHas('event', function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('organization_name', 'like', $term);
            });
        }

        // Apply sorting
        $query->orderBy('updated_at', $this->filters['sortDirection'] ?? 'desc');

        // Paginate results
        $eventhistories = $query->paginate(8);

//        dd($eventhistories);

        return view('livewire.request.history.index', compact('eventhistories'));
    }


}
