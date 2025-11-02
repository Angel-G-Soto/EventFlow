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
use Livewire\Component;

/**
 * Class Details
 *
 * Presents a single Venue's details.
 * Accepts a Venue or ID in mount() and renders the details Blade view.
 */
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
        return view('livewire.request.pending.details', compact('docs'));
    }
}
