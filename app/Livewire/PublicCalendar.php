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
    public string $search = '';
    public ?int $categoryId = null;

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
    public array $categories = [];

    // Toggle Filter By My Venues
    /**
     * Toggles the "Filter by My Venues" flag when allowed.
     *
     * @return void
     */
    public function toggleFilterMyVenues(): void
    {
        if (! $this->canFilterMyVenues) {
            return;
        }

        $this->filterMyVenues = !$this->filterMyVenues;
    }

    /**
     * Initializes the calendar to the current week and loads category/venue state.
     *
     * @return void
     */
    public function mount(): void
    {
        $monday = now()->startOfWeek(CarbonImmutable::MONDAY);
        $this->weekStart = $monday->toDateString();

        if (Auth::check()) {
            $user = Auth::user()->loadMissing('roles', 'department.venues');
            $this->canFilterMyVenues = $user->roles->contains('name', 'venue-manager');
            $this->managedVenueIds = $user->department?->venues->pluck('id')->all() ?? [];
        }

        // Load categories for filter dropdown
        $this->categories = app(\App\Services\CategoryService::class)
            ->getAllCategories()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /**
     * Moves the week window forward or backward by one week.
     *
     * @param string $dir Either "prev" or "next".
     * @return void
     */
    public function goWeek(string $dir): void
    {
        $start = CarbonImmutable::parse($this->weekStart);
        $this->weekStart = $start->modify($dir === 'prev' ? '-7 days' : '+7 days')->toDateString();
    }

    /**
     * Explicit applySearch handler (matches other search bars).
     *
     * @return void
     */
    public function applySearch(): void
    {
        // Simply trigger a re-render; inputs are already bound via defer.
    }

    /**
     * Returns events for the current week, applying filters and search.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function weekEvents(): array
    {
        $start = CarbonImmutable::parse($this->weekStart)->startOfDay();
        $end   = $start->addDays(7);

        $events = $this->allApprovedPublic();

        // Normalize category ids for consistent filtering (supports many-to-many, single column, JSON, or CSV)
        $events = array_map(function ($e) {
            $ids = [];

            if (isset($e['category_ids'])) {
                if (is_array($e['category_ids'])) {
                    $ids = array_merge($ids, $e['category_ids']);
                } elseif (is_string($e['category_ids'])) {
                    $decoded = json_decode($e['category_ids'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $ids = array_merge($ids, $decoded);
                    } else {
                        $ids = array_merge($ids, array_map('trim', explode(',', $e['category_ids'])));
                    }
                } else {
                    $ids[] = (int) $e['category_ids'];
                }
            }

            if (isset($e['categories']) && is_array($e['categories'])) {
                $ids = array_merge($ids, collect($e['categories'])->pluck('id')->all());
            }

            if (isset($e['category_id']) && $e['category_id'] !== null) {
                $ids[] = $e['category_id'];
            }

            $e['category_ids'] = array_values(array_unique(array_map('intval', $ids)));
            return $e;
        }, $events);

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

        // Text search by title or organization (case-insensitive)
        $term = trim(mb_strtolower($this->search));
        $hasCategoryFilter = ($this->categoryId !== null && $this->categoryId !== '');
        $hasWideFilter = ($term !== '') || $hasCategoryFilter;

        if ($term !== '') {
            $events = array_filter($events, function ($e) use ($term) {
                $title = mb_strtolower((string) ($e['title'] ?? ''));
                $org   = mb_strtolower((string) ($e['organization_name'] ?? ''));
                return str_contains($title, $term) || str_contains($org, $term);
            });
        }

        // Category filter (if provided on the event payload)
        if ($hasCategoryFilter) {
            $catId = (int) $this->categoryId;
            $events = array_filter($events, function ($e) use ($catId) {
                // Support both single category_id column and many-to-many category_ids list
                $ids = $e['category_ids'] ?? [];
                if (!is_array($ids)) {
                    $ids = $ids ? [(int) $ids] : [];
                }

                if (isset($e['category_id']) && $e['category_id'] !== null) {
                    $ids[] = (int) $e['category_id'];
                }

                $ids = array_unique(array_map('intval', $ids));
                return in_array($catId, $ids, true);
            });
<<<<<<< HEAD

            // Debug: log the filter context and remaining count to validate category filtering
            try {
                logger()->debug('PublicCalendar category filter applied', [
                    'selected_category_id' => $catId,
                    'remaining_after_category' => count($events),
                    'sample_events' => array_slice($events, 0, 3),
                ]);
            } catch (\Throwable) {
                // best-effort logging only
            }
=======
>>>>>>> 9b88c74d82925146ae2b530ae1409c95bbd39343
        }

        // Week window only applies when no search term or category is provided
        if (! $hasWideFilter) {
            $events = array_filter($events, function ($e) use ($start, $end) {
                $s = CarbonImmutable::parse($e['start_time']);
                return $s->betweenIncluded($start, $end);
            });
        }

        return array_values($events);
    }

    /**
     * Loads a single event for modal display and opens the appropriate dialog.
     *
     * @param int $id
     * @return void
     */
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

    /**
     * Renders the public calendar view with events grouped by day.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        $start = CarbonImmutable::parse($this->weekStart);
        $events = $this->weekEvents();

        $term = trim($this->search ?? '');
        $hasCategoryFilter = ($this->categoryId !== null && $this->categoryId !== '');
        $hasWideFilter = ($term !== '') || $hasCategoryFilter;

        if ($hasWideFilter && !empty($events)) {
            $dates = collect($events)
                ->map(fn($e) => CarbonImmutable::parse($e['start_time'])->toDateString())
                ->unique()
                ->sort()
                ->values();

            $firstDate = CarbonImmutable::parse($dates->first());
            $lastDate  = CarbonImmutable::parse($dates->last());
            $weekLabel = $firstDate->format('M j, Y') . ' – ' . $lastDate->format('M j, Y');
        } else {
            $dates = collect(range(0, 6))->map(fn($i) => $start->addDays($i)->toDateString());
            $weekLabel = $start->format('M j') . ' – ' . $start->addDays(6)->format('M j, Y');
        }

        $eventsByDay = [];
        foreach ($dates as $date) {
            $eventsByDay[$date] = [];
        }

        foreach ($events as $e) {
            $key = CarbonImmutable::parse($e['start_time'])->toDateString();
            if (!array_key_exists($key, $eventsByDay)) {
                $eventsByDay[$key] = [];
                $dates = $dates->concat([$key])->unique()->sort()->values();
            }
            $eventsByDay[$key][] = $e;
        }

        return view('livewire.public-calendar', [
            'days'        => $dates->map(fn($d) => CarbonImmutable::parse($d)),
            'eventsByDay' => $eventsByDay,
            'weekLabel'   => $weekLabel,
            'canFilterMyVenues' => $this->canFilterMyVenues,
        ]);
    }
}
