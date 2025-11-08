<?php

namespace App\Livewire;

use App\Models\Event;
use App\Services\EventService;
use App\Services\VenueService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // or 'layouts.public' if you have one
class PublicCalendar extends Component
{
    // Week anchor (Monday)
    public string $weekStart; // ISO date (YYYY-MM-DD)
    public bool $filterMyVenues = false;

    // Read-only public, approved events (mocked for now)
    protected function allApprovedPublic(): array
    {
        return Event::where('status', 'approved')->get()->toArray();
    }

    public ?array $modal = null; // {title, venue, time, summary}

    // Toggle Filter By My Venues
    public function toggleFilterMyVenues(): void
    {
        $this->filterMyVenues = !$this->filterMyVenues;
    }

    public function mount(): void
    {
        $monday = now()->startOfWeek(CarbonImmutable::MONDAY);
        $this->weekStart = $monday->toDateString();
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
        if ($this->filterMyVenues && auth()->check()) {
            $user = auth()->user();
            $isVenueManager = $user->roles()->where('name', 'venue-manager')->exists();
            if ($isVenueManager) {
                $managedVenueIds = $user->department->venues->pluck('id')->toArray();
                $events = array_filter($events, fn($e) => in_array($e['venue_id'], $managedVenueIds));
            }
        }

        return array_values($events);
    }

    public function openEvent(int $id): void
    {
        $e = collect($this->weekEvents())->firstWhere('id', $id);
        if (!$e) return;
        $this->modal = [
            'title'   => $e['title'],
            'venue'   => app(VenueService::class)->findById($e['venue_id'])->code,
            'time'    => sprintf(
                '%s — %s',
                CarbonImmutable::parse($e['start_time'])->format('D, M j • g:ia'),
                CarbonImmutable::parse($e['end_time'])->format('g:ia')
            ),
            'description' => $e['description'] ?? '',
            'organization_name' => $e['organization_name'] ?? '',
        ];

        $this->dispatch('bs:open', id: 'eventDetails');
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
        ]);
    }
}
