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

namespace App\Livewire\Request\Org;

use App\Models\Event;
use App\Services\EventRequestPdfDownloadService;
use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\HasJustification;

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
     * Cancels or withdraws the event (depending on status) after validation.
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
        $eventService = app(EventService::class);
        if ($this->event->status === 'approved'){
            $eventService->cancelEvent($this->event,Auth::user(),$this->justification);
        }
        else{
            $eventService->withdrawEvent($this->event,Auth::user(), $this->justification);
        }
        $this->redirectRoute('user.index');
    }

    /**
     * Returns to the user index without taking action.
     *
     * @return void
     */
    public function back()
    {
        $this->redirectRoute('user.index');
    }

    /**
     * Triggers a PDF download of the approved request.
     *
     * @param EventRequestPdfDownloadService $pdfDownloadService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadSummary(EventRequestPdfDownloadService $pdfDownloadService)
    {
        return $pdfDownloadService->download($this->event);
    }


    /**
     * Renders the organization request details view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->authorize('viewMyRequest', $this->event);

        $eventService = app(EventService::class);
        $event = $eventService->loadEventDetails($this->event);
        $docs = $eventService->getEventDocuments($event)->toArray();
        $terminalNotice = $eventService->getTerminalActionNotice($event);
        return view('livewire.request.org.details', [
            'docs' => $docs,
            'terminalNotice' => $terminalNotice,
        ]);
    }
}
