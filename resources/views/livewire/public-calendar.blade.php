<div class="container py-4">
    <!-- Welcoming message with transparent background, centered, and elegant styling -->
    <div class="alert alert-transparent text-center py-4 mb-4" style="background-color: rgba(255, 255, 255, 0.85); border: none;">
        <h4 class="alert-heading text-dark fw-semibold">Welcome to the Eventflow!</h4>
        <p class="lead text-dark">Stay updated with all the exciting events this week. If you want to request participation or learn more, click the button below to initiate the request on the Nexo platform.</p>
        <a href="https://www.example.com" target="_blank" class="btn btn-success mt-3" role="button" aria-label="Initiate your request on the Nexo platform">Initiate Request</a> <!-- Dark Green Button -->
        <hr class="my-4" style="border-top: 1px solid #ccc;">
        <p class="mb-0 text-dark">We hope you have an amazing time at the events!</p>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Events This Week</h1>
        <div class="btn-group">
            <button class="btn btn-outline-success btn-sm" wire:click="goWeek('prev')" aria-label="Previous week">&laquo; Previous</button> <!-- Dark Green Outline -->
            <span class="btn btn-outline-success btn-sm disabled">{{ $weekLabel }}</span> <!-- Dark Green Outline -->
            <button class="btn btn-outline-success btn-sm" wire:click="goWeek('next')" aria-label="Next week">Next &raquo;</button> <!-- Dark Green Outline -->
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
                            <li class="list-group-item small d-flex justify-content-between align-items-center">
                                <button class="btn btn-link p-0 text-start text-success flex-grow-1 text-truncate"
                                        wire:click="openEvent({{ $e['id'] }})"
                                        aria-label="Open event {{ $e['title'] }} details">
                                    <div class="fw-semibold">{{ $e['title'] }}</div>
                                </button>
                                <div class="text-muted ms-3">
                                    {{ \Carbon\Carbon::parse($e['start_time'])->format('g:ia') }}
                                    - {{ \Carbon\Carbon::parse($e['end_time'])->format('g:ia') }}
                                </div>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>{{ $modal['title'] ?? 'Event' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $modal['venue'] ?? '' }}</div>
                    <div class="mb-3"><i class="bi bi-clock me-2"></i>{{ $modal['time'] ?? '' }}</div>
                    <div class="mb-3"><i class="bi bi-person-workspace me-2"></i>Organized by: {{ $modal['organization_name'] ?? '' }}</div>
                    <label class="mb-2 fw-semibold">Event Description:</label>
                    <p class="mb-0">{{ $modal['description'] ?? '' }}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close details">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        function ensureBodyScrollable() {
            try {
                // If no modal remains visible, restore scrolling and remove stray backdrops
                const anyVisible = document.querySelector('.modal.show');
                if (!anyVisible) {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                }
            } catch (_) { /* noop */ }
        }

        document.addEventListener('hidden.bs.modal', ensureBodyScrollable);

        document.addEventListener('livewire:init', () => {
            Livewire.on('bs:open', ({ id }) => {
                const el = document.getElementById(id);
                if (el) bootstrap.Modal.getOrCreateInstance(el).show();
            });
            Livewire.on('bs:close', ({ id }) => {
                const el = document.getElementById(id);
                if (!el) return;
                const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                inst.hide();
                // Safety: ensure scrolling restored after hide
                setTimeout(ensureBodyScrollable, 0);
            });
        });
    })();
</script>
