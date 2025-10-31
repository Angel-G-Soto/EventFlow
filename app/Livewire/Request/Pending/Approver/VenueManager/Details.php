<?php

namespace App\Livewire\Request\Pending\Approver\VenueManager;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Details extends Component
{
    use WithPagination;
    public string $paginationTheme = 'bootstrap';
    public Event $event;

    public string $justification = '';

    /**
     * Livewire lifecycle hook. Receives the bound Event via route model binding,
     * initializes the component state, and computes any schedule conflicts.
     *
     * @param  Event  $event  The event to display (injected by Laravel).
     * @return void
     */
    public function mount(Event $event): void
    {
        $this->event = $event;

    }

    /**
     * Build the base query for conflicts (reused for exists() and paginate()).
     */
    protected function conflictsQuery(): Builder
    {
        return Event::query()
            ->whereKeyNot($this->event->id)
//            ->when(isset($this->event->venue_id),
//                fn ($q) => $q->where('venue_id', $this->event->venue_id)
//            )
//            // overlap: start < other.end AND end > other.start
//            ->where('start_time', '<', $this->event->end_time)
//            ->where('end_time',   '>', $this->event->start_time)
            ->orderBy('start_time')
            ->select(['id', 'title', 'start_time', 'end_time', 'organization_nexo_name']);
    }

    /**
     * Quick boolean for “show alert/list?” without pulling rows.
     */
    public function getHasConflictsProperty(): bool
    {
        return true;
//        return $this->conflictsQuery()->exists();
    }

    /**
     * Public method to re-run the conflict query.
     *
     * Call this if the current event’s time window or venue changes on this page
     * and you want to refresh the conflict list without a full reload.
     *
     * @return void
     */
    public function refreshConflicts(): void
    {
        $this->resetPage('conflictsPage');
    }

    public function save()
    {
        $this->validate(['justification' => 'required|min:10']);
        // ... do your action
        $this->redirectRoute('approver.index');
    }

    public function approve()
    {
        // ... do your action
        $this->redirectRoute('approver.index');
    }

    public function back()
    {
        $this->redirectRoute('approver.pending.index');
    }


    public function render()
    {
        $docs = [
            ['title' => 'Syllabus', 'url' => asset('23382.pdf'), 'description' => 'Fall 2025'],
            ['title' => 'Reglamento interno', 'url' => asset('REGLAMENTO-INTERNO.pdf'), 'description' => 'Fall 2025']
        ];
        $conflicts = $this->conflictsQuery()->paginate(5, ['*'], 'conflictsPage');

        return view('livewire.request.pending.approver.venuemanager.details', compact('docs'),compact('conflicts'));
    }
}
