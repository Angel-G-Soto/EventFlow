<?php

namespace App\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // or 'layouts.public' if you have one
class PublicCalendar extends Component
{
  // Week anchor (Monday)
  public string $weekStart; // ISO date (YYYY-MM-DD)

  // Read-only public, approved events (mocked for now)
  protected function allApprovedPublic(): array
  {
    return [
      // id, title, venue, starts_at, ends_at, summary
      ['id' => 451, 'title' => 'Faculty Town Hall', 'venue' => 'Auditorium A', 'starts_at' => '2025-11-03 10:00', 'ends_at' => '2025-11-03 11:30', 'summary' => 'Campus-wide update & Q&A'],
      ['id' => 460, 'title' => 'Night Music Fest', 'venue' => 'Courtyard', 'starts_at' => '2025-11-06 19:30', 'ends_at' => '2025-11-06 22:00', 'summary' => 'Student bands & food trucks'],
      ['id' => 472, 'title' => 'Chem Outreach', 'venue' => 'Lab West', 'starts_at' => '2025-11-05 14:00', 'ends_at' => '2025-11-05 16:00', 'summary' => 'Hands-on demos for visitors'],
    ];
  }

  public ?array $modal = null; // {title, venue, time, summary}

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

    return array_values(array_filter($this->allApprovedPublic(), function ($e) use ($start, $end) {
      $s = CarbonImmutable::parse($e['starts_at']);
      return $s->betweenIncluded($start, $end);
    }));
  }

  public function openEvent(int $id): void
  {
    $e = collect($this->weekEvents())->firstWhere('id', $id);
    if (!$e) return;
    $this->modal = [
      'title'   => $e['title'],
      'venue'   => $e['venue'],
      'time'    => sprintf(
        '%s — %s',
        CarbonImmutable::parse($e['starts_at'])->format('D, M j • g:ia'),
        CarbonImmutable::parse($e['ends_at'])->format('g:ia')
      ),
      'summary' => $e['summary'] ?? '',
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
      $key = CarbonImmutable::parse($e['starts_at'])->toDateString();
      if (isset($eventsByDay[$key])) $eventsByDay[$key][] = $e;
    }

    return view('livewire.public-calendar', [
      'days'        => $days,
      'eventsByDay' => $eventsByDay,
      'weekLabel'   => $start->format('M j') . ' – ' . $start->addDays(6)->format('M j, Y'),
    ]);
  }
}
