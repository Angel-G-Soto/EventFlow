<?php

namespace App\Livewire\Request\Pending;

use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.user')]
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

    #[On('filters-changed')]
    public function onFiltersChanged(array $filters): void
    {
        // Map dispatched keys to local filters
        $this->filters['role'] = $filters['role'] ?? '';
        $this->filters['searchTitle'] = $filters['searchTitle'] ?? '';
        $this->filters['sortDirection'] = $filters['sortDirection'] ?? 'desc';

        $this->resetPage(); // reset pagination
    }

    public function render()
    {
        $query = app(EventService::class);

        $roles = $this->filters['role'] ? [$this->filters['role']] : []; // if empty, send empty array

        if (!empty($roles)) {
            $query = $query->genericGetPendingRequestsV2(Auth::user(), $roles);
        } else {
            // no role filter
            $query = $query->genericGetPendingRequestsV2(Auth::user());
        }

        // Apply search filter
        if (!empty($this->filters['searchTitle'])) {
            $query->where('title', 'like', '%'.$this->filters['searchTitle'].'%');
        }

        // Apply sort direction
        $sortDir = in_array($this->filters['sortDirection'], ['asc','desc']) ? $this->filters['sortDirection'] : 'desc';
        $query->orderBy('updated_at', $sortDir);

        $events = $query->paginate(8);

        return view('livewire.request.pending.index', compact('events'));
    }
}
