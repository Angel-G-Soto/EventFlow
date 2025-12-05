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
 * Back action.
 * @return mixed
 */

    public function back()
    {
        $this->redirectRoute('user.index');
    }

/**
 * Trigger the PDF export for the approved request.
 * @return \Symfony\Component\HttpFoundation\Response
 */

    public function downloadSummary(EventRequestPdfDownloadService $pdfDownloadService)
    {
        return $pdfDownloadService->download($this->event);
    }
/**
 * Render the venue details Blade view.
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
