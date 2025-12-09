<?php

/**
 * Livewire Component: Venue Details
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Displays a single venue with: name, department, current manager,
 * capacity, opening time, and closing time.
 *
 * Responsibilities:
 * - Load a Venue (via ID or model binding) in mount().
 * - Expose the model to the Blade view.
 * - Provide small helpers for formatting when needed.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Request\Pending;

use App\Models\Event;
use App\Services\EventHistoryService;
use App\Services\EventService;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\HasJustification;
use App\Policies\EventPolicy;
use Mockery\Matcher\Not;

/**
 * Class Details
 *
 * Presents a single Venue's details.
 * Accepts a Venue or ID in mount() and renders the details Blade view.
 */
#[Layout('layouts.app')]
class Details extends Component
{
    use HasJustification;
/**
 * @var Event
 */
    public Event $event;
/**
 * @var string
 */
    public string $justification = '';

    /**
     * Indicates whether the justification meets the minimum length.
     *
     * @return bool
     */
    public function getIsReadyProperty(): bool
    {
        return strlen(trim($this->justification)) >= 10;
    }

    /**
     * Rejects the event with a justification and redirects to pending index.
     *
     * @return void
     */
    public function save()
    {


        $this->validate([
            'justification' => $this->justificationRules(true),
        ], [], [
            'justification' => 'justification',
        ]);
        // ... do your action


        app(EventService::class)->denyEvent($this->justification, $this->event, Auth::user());

        $this->redirectRoute('approver.pending.index');
    }

    /**
     * Approves the event and redirects to pending index.
     *
     * @return void
     */
    public function approve()
    {


        app(EventService::class)->approveEvent($this->event, Auth::user());

        $this->redirectRoute('approver.pending.index');
    }

    /**
     * Returns to the pending index without taking action.
     *
     * @return void
     */
    public function back()
    {
        $this->redirectRoute('approver.pending.index');
    }

    /**
     * Renders the pending request details view with documents and conflicts.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
//        dd($this->event);

        $event = $this->event;
        $this->authorize('manageMyPendingRequests', $event);

        $eventService = app(EventService::class);
        $event->loadMissing([
            'categories:id,name',
            'venue:id,name,code,description',
        ]);
        $docs = $eventService->getEventDocuments($event)->toArray();
        $conflicts = $eventService->conflictingEvents($event)->paginate(4);
//        dd($conflicts);
        return view('livewire.request.pending.details', compact( 'docs', 'conflicts'));
    }
}
