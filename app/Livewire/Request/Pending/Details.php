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
/**
 * @var Event
 */
    public Event $event;
/**
 * @var string
 */
    public string $justification = '';
/**
 * GetIsReadyProperty action.
 * @return bool
 */
    public function getIsReadyProperty(): bool
    {
        return strlen(trim($this->justification)) >= 10;
    }
/**
 * Save action.
 * @return mixed
 */

    public function save()
    {


        $this->validate(['justification' => 'required|min:10|max:255']);
        // ... do your action


        app(EventService::class)->denyEvent($this->justification, $this->event, Auth::user());

        $this->redirectRoute('approver.pending.index');
    }
/**
 * Approve action.
 * @return mixed
 */

    public function approve()
    {


        app(EventService::class)->approveEvent($this->event, Auth::user());

        $this->redirectRoute('approver.pending.index');
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
