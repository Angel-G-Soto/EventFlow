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
#[Layout('layouts.user')]
class Details extends Component
{
    public Event $event;
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
        $this->redirectRoute('approver.history.index');
    }
/**
 * Approve action. Redirects to request history
 * @return mixed
 */

    public function approve()
    {
        // ... do your action
        $this->redirectRoute('approver.history.index');
    }
/**
 * Back action. Redirects to request history
 * @return mixed
 */

    public function back()
    {
        $this->redirectRoute('approver.history.index');
    }
/**
 * Render the Blade view for the venue details page.
 * @return \Illuminate\Contracts\View\View
 */


    public function render()
    {
        $docs = [
            ['title' => 'Syllabus', 'url' => asset('23382.pdf'), 'description' => 'Fall 2025'],
            ['title' => 'Reglamento interno', 'url' => asset('REGLAMENTO-INTERNO.pdf'), 'description' => 'Fall 2025']
        ];
        return view('livewire.request.history.details', compact('docs'));
    }
}
