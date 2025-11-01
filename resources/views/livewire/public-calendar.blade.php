<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Events This Week</h1>
    <div class="btn-group">
      <button class="btn btn-outline-secondary btn-sm" wire:click="goWeek('prev')" aria-label="Previous week">&laquo;
        Previous</button>
      <span class="btn btn-outline-secondary btn-sm disabled">{{ $weekLabel }}</span>
      <button class="btn btn-outline-secondary btn-sm" wire:click="goWeek('next')" aria-label="Next week">Next
        &raquo;</button>
    </div>
  </div>

  {{-- Simple weekly grid --}}
  <div class="row row-cols-1 row-cols-md-7 g-2">
    @foreach($days as $day)
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-header py-2">
          <strong>{{ $day->format('D') }}</strong>
          <span class="text-muted">{{ $day->format('M j') }}</span>
        </div>
        <ul class="list-group list-group-flush">
          @forelse($eventsByDay[$day->toDateString()] ?? [] as $e)
          <li class="list-group-item small">
            <button class="btn btn-link p-0 text-start" wire:click="openEvent({{ $e['id'] }})"
              aria-label="Open event {{ $e['title'] }} details">
              <div class="fw-semibold">{{ $e['title'] }}</div>
              <div class="text-muted">
                {{ \Carbon\Carbon::parse($e['starts_at'])->format('g:ia') }}
                • {{ $e['venue'] }}
              </div>
            </button>
          </li>
          @empty
          <li class="list-group-item text-muted small">No events</li>
          @endforelse
        </ul>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Event details modal --}}
  <div class="modal fade" id="eventDetails" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>{{ $modal['title'] ?? 'Event' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $modal['venue'] ?? '' }}</div>
          <div class="mb-3"><i class="bi bi-clock me-2"></i>{{ $modal['time'] ?? '' }}</div>
          <p class="mb-0">{{ $modal['summary'] ?? '' }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close details">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      Livewire.on('bs:open', ({ id }) => {
        const el = document.getElementById(id);
        if (el) new bootstrap.Modal(el).show();
      });
    });
  </script>
</div>