<?php

namespace App\Livewire\Request\Pending\Approver;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\Event;
#[layout('components.layouts.header.public')]
class Index extends Component
{
    use WithPagination;
    public string $paginationTheme = 'bootstrap';
    public array $filters = [
        'categories' => [],
        'venues'     => [],
        'orgs'       => [],
    ];

    #[On('filters-changed')]
    public function onFiltersChanged(array $categories = [], array $venues = [], array $orgs = []): void
    {
        $this->filters['categories'] = array_map('intval', $categories);
        $this->filters['venues']     = array_map('intval', $venues);
        $this->filters['orgs']       = array_map('intval', $orgs);

        $this->resetPage(); // go back to page 1 after changing filters
    }

    public function render()
    {
        $q = Event::query()->with(['venue','categories']);

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
            $q->whereIn('organization_nexo_id', $this->filters['orgs']);
        }

        $events = $q->orderByDesc('created_at')->paginate(8);

        return view('livewire.request.pending.approver.index', compact('events'));
    }
}
