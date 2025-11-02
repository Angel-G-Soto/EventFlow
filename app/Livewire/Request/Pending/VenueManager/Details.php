<?php

/**
 * Livewire Component: Venue Details
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Displays a single venue: name, department, current manager, capacity, opening/closing time.
 *
 * Responsibilities:
 * - Load a Venue (via ID or model binding) in mount()
 * - Expose the model to the Blade view
 * - Provide small formatting helpers when needed
 *
 * @since 2025-11-01
 */

namespace App\Livewire\Request\Pending\VenueManager;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Class Details
 *
 * Presents a single Venue's details.
 * Accepts a Venue or ID in mount() and renders the details Blade view.
 */
class Details extends Component
{
    use WithPagination;
/**
 * @var string
 */
    public string $paginationTheme = 'bootstrap';
/**
 * @var Event
 */
    public Event $event;
/**
 * @var string
 */

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
/**
 * Save action.
 * @return mixed
 */

    public function save()
    {
        $this->validate(['justification' => 'required|min:10']);
        // ... do your action
        $this->redirectRoute('approver.index');
    }
/**
 * Approve action.
 * @return mixed
 */

    public function approve()
    {
        // ... do your action
        $this->redirectRoute('approver.index');
    }
/**
 * Back action.
 * @return mixed
 */

    public function back()
    {
        $this->redirectRoute('approver.pending.index');
    }
/**
 * Render the venue details Blade view.
 * @return \Illuminate\Contracts\View\View
 */


    public function render()
    {
        $docs = [
            ['title' => 'Syllabus', 'url' => asset('23382.pdf'), 'description' => 'Fall 2025'],
            ['title' => 'Reglamento interno', 'url' => asset('REGLAMENTO-INTERNO.pdf'), 'description' => 'Fall 2025']
        ];
        $conflicts = $this->conflictsQuery()->paginate(5, ['*'], 'conflictsPage');

        return view('livewire.request.pending.venuemanager.details', compact('docs'),compact('conflicts'));
    }
}
