<?php

namespace App\Livewire;

use App\Models\Event;
use App\Services\EventService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // or 'layouts.public' if you have one
class PublicCalendar extends Component
{
    // Week anchor (Monday)
    public string $weekStart; // ISO date (YYYY-MM-DD)
    public bool $filterMyVenues = false;
    public bool $canFilterMyVenues = false;
    public array $managedVenueIds = [];
    public array $docs = [];

    // Read-only public, approved events (mocked for now)
    protected function allApprovedPublic(): array
    {
            //             return Event::whereIn('status', ['approved', 'completed'])
            // ->orderBy('start_time')
            // ->orderBy('end_time')
            // ->get()
            // ->toArray();
        $approvedEvents = app(EventService::class)->getApprovedPublicEvents();
        return $approvedEvents;

    }

    public ?array $modal = null; // {title, venue, time, summary}

    // Toggle Filter By My Venues
    public function toggleFilterMyVenues(): void
    {
        if (! $this->canFilterMyVenues) {
            return;
        }

        $this->filterMyVenues = !$this->filterMyVenues;
    }

    public function mount(): void
    {
        $monday = now()->startOfWeek(CarbonImmutable::MONDAY);
        $this->weekStart = $monday->toDateString();

        if (Auth::check()) {
            $user = Auth::user()->loadMissing('roles', 'department.venues');
            $this->canFilterMyVenues = $user->roles->contains('name', 'venue-manager');
            $this->managedVenueIds = $user->department?->venues->pluck('id')->all() ?? [];
        }
    }

    public function goWeek(string $dir): void
    {
        $start = CarbonImmutable::parse($this->weekStart);
        $this->weekStart = $start->modify($dir === 'prev' ? '-7 days' : '+7 days')->toDateString();
    }

    protected function weekEvents(): array
    {
        $start = CarbonImmutable::parse($this->weekStart)->startOfDay();
        $end   = $start->addDays(7);

        $events = array_filter($this->allApprovedPublic(), function ($e) use ($start, $end) {
            $s = CarbonImmutable::parse($e['start_time']);
            return $s->betweenIncluded($start, $end);
        });

        // Apply "Filter By My Venues" if enabled and user is venue-manager
        if ($this->filterMyVenues && $this->canFilterMyVenues && ! empty($this->managedVenueIds)) {
            $events = array_filter(
                $events,
                fn ($e) => in_array($e['venue_id'], $this->managedVenueIds, true)
            );
        } elseif ($this->filterMyVenues && $this->canFilterMyVenues) {
            // User is a venue manager but does not manage any venues, so the list should be empty
            $events = [];
        }

        return array_values($events);
    }

    public function openEvent(int $id): void
    {
        // $event =  Event::find($id);
        $event = app(EventService::class)->findPublicEventById($id); // Fetch the event by ID (this returns an instance of the Event model)
        $this->modal = [
            'event' => $event
        ];

        if (!$event) return;

        if (Auth::check()) {
            $canViewManagedVenueEvent = $this->canFilterMyVenues
                && in_array($event->venue_id, $this->managedVenueIds, true);

            if ($canViewManagedVenueEvent) {
                $this->dispatch('bs:open', id: 'publicEventDetails');
                $this->docs = app(EventService::class)->getEventDocuments($event)->toArray();
            } else {
                $this->dispatch('bs:open', id: 'eventDetails');
            }
        } else{
            $this->dispatch('bs:open', id: 'eventDetails');
        }

        // Pass the event model directly to the modal
//        $user_roles = app(UserService::class)->getUserRoles();


//        $this->dispatch('bs:open', id: 'eventDetails');
    }

    public function render()
    {
        $start = CarbonImmutable::parse($this->weekStart);
        $days  = collect(range(0, 6))->map(fn($i) => $start->addDays($i));

        $eventsByDay = [];
        foreach ($days as $d) {
            $eventsByDay[$d->toDateString()] = [];
        }

        foreach ($this->weekEvents() as $e) {
            $key = CarbonImmutable::parse($e['start_time'])->toDateString();
            if (isset($eventsByDay[$key])) $eventsByDay[$key][] = $e;
        }

        return view('livewire.public-calendar', [
            'days'        => $days,
            'eventsByDay' => $eventsByDay,
            'weekLabel'   => $start->format('M j') . ' – ' . $start->addDays(6)->format('M j, Y'),
            'canFilterMyVenues' => $this->canFilterMyVenues,
        ]);
    }
}
