<?php

/**
 * Livewire Component: Show
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Displays a single record (e.g., Event, Venue, Requirement) with its fields.
 *
 * Responsibilities:
 * - Load a model in mount() (by ID or route model binding).
 * - Expose the model/data to the Blade view.
 * - Provide lightweight helpers/formatters if needed.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Venue;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Display a single Venue's details (read-only).
 *
 * Shows core attributes and related entities:
 * - Name, Department, Current Manager, Capacity, Opening/Closing times.
 *
 * @property Venue $venue The venue being displayed.
 */
#[layout('components.layouts.header.public')]
/**
 * Class Show
 *
 * Livewire 'show' component for presenting a single record.
 * Accepts an ID or model instance in mount() and renders a Blade view.
 */
class Show extends Component
{
    /** @var Venue The venue being displayed. */
    public Venue $venue;

    /**
     * Hydrate the component with the given Venue model.
     *
     * Uses route-model binding: /venues/{venue}.
     * Eager loads minimal fields from relations for performance.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function mount(Venue $venue): void
    {
        $this->venue = $venue->load([
            'manager' => fn ($q) => $q->select('id', 'first_name', 'last_name'),
            'department' => fn ($q) => $q->select('id', 'name'), // adjust if your departments table differs
        ]);
    }

    /**
     * Format a DB TIME string to "HH:MM".
     * Accepts either "HH:MM" or "HH:MM:SS" and returns null for empty values.
     *
     * @param  string|null  $time
     * @return string|null "HH:MM" or null if not set.
     */
    protected function fmtTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    /**
     * Render the details view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.venue.managers.show', [
            'open'  => $this->fmtTime($this->venue->opening_time),
            'close' => $this->fmtTime($this->venue->closing_time),
        ]);
    }
}
