<?php

/**
 * Livewire Component: Venue Details
 *
 * Part of the EventFlow project (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Renders a venue detail page including: name, department, current manager,
 * capacity, opening time, and closing time.
 *
 * Responsibilities:
 * - Load a single Venue instance (by ID or via route-model binding).
 * - Provide data to the Blade view.
 * - Offer simple helpers for formatted fields where useful.
 *
 * @author  EventFlow
 * @license MIT
 * @since   2025-11-01
 */

namespace App\Livewire\Request\History;

use App\Models\EventHistory;
use App\Services\EventHistoryService;
use App\Services\EventService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

use App\Models\Event;

/**
 * Class Details
 *
 * Livewire component responsible for presenting a single Venue's details.
 * Accepts a Venue (or ID) on mount and exposes it to the Blade view.
 *
 * @package App\Livewire\Venue
 *
 */
#[Layout('layouts.app')]
class Details extends Component
{
    public EventHistory $eventHistory;
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

    public function save(): void
    {
        $this->validate(['justification' => 'required|min:10']);
        // ... do your action
        $eventHistoryService = app(EventService::class);
        $eventHistoryService->cancelEvent($this->eventHistory->event,Auth::user(),$this->justification);
        $this->redirectRoute('approver.history.index');
    }
/**
 * Approve action. Redirects to request history
 * @return mixed
 */

    public function approve(): void
    {
        // ... do your action
        app(EventService::class)->approve($this->eventHistory->event);
        $this->redirectRoute('approver.history.index');
    }
/**
 * Back action. Redirects to request history
 * @return mixed
 */

    public function back():void
    {
        $this->redirectRoute('approver.history.index');
    }
/**
 * Render the Blade view for the venue details page.
 * @return \Illuminate\Contracts\View\View
 */


    public function render()
    {

        $docs = app(EventService::class)->getEventDocuments($this->eventHistory->event)->toArray();

        // Use document service method that accepts event_id and return the array of docs.

        return view('livewire.request.history.details', compact('docs'));
    }
}


//$eventDetails = app(NotificationService::class)->getEventDetails($this->eventHistory->event);
//$approverEmails = app(EventHistoryService::class)->getEventApproverEmails($this->eventHistory->event);
//app(NotificationService::class)->dispatchCancellationNotifications(
//    recipientEmails: $approverEmails,
//    eventDetails: $eventDetails,
//    justification: 'Example',
//);
//
//        app(NotificationService::class)->dispatchApprovalRequiredNotification(
//            approverEmail: 'andres.torres18@upr.edu',eventDetails: $eventDetails
//        );
//
//        dd($this->eventHistory);
