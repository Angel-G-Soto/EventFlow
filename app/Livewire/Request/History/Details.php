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
use App\Services\EventRequestPdfDownloadService;
use App\Services\EventService;
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
        $this->authorize('manageMyApprovalHistory', $this->eventHistory);

        $this->validate(['justification' => 'required|min:10']);
        // ... do your action
        $eventService = app(EventService::class);
        $eventService->cancelEvent($this->eventHistory->event,Auth::user(),$this->justification);
        $this->redirectRoute('approver.history.index');
    }
/**
 * Approve action. Redirects to request history
 * @return mixed
 */

    public function approve(): void
    {
        $this->authorize('manageMyApprovalHistory', $this->eventHistory);

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
 * Trigger the PDF export for the approved request.
 * @return \Symfony\Component\HttpFoundation\Response
 */

    public function downloadSummary(EventRequestPdfDownloadService $pdfDownloadService)
    {
        return $pdfDownloadService->download($this->eventHistory->event);
    }
/**
 * Render the Blade view for the venue details page.
 * @return \Illuminate\Contracts\View\View
 */


    public function render()
    {
        $this->authorize('manageMyApprovalHistory', $this->eventHistory);

        $event = tap($this->eventHistory->event)->loadMissing([
            'categories:id,name',
            'venue:id,name,code,description',
        ]);

        $docs = app(EventService::class)->getEventDocuments($event)->toArray();

        // Use document service method that accepts event_id and return the array of docs.

        return view('livewire.request.history.details', compact('docs'));
    }
}
