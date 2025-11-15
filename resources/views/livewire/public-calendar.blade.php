<div class="container py-4">
    <!-- Welcoming message with a grayer background, shadow, border, centered, and elegant styling -->
    <div class="alert alert-transparent text-center py-4 mb-4" style="background-color: rgba(240, 240, 240, 0.85); border: 1px solid #ddd; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h4 class="alert-heading text-dark fw-semibold">Welcome to the Eventflow!</h4>
        <p class="lead text-dark">Stay updated with all the exciting events this week. If you want to request participation or learn more, click the button below to initiate the request on the Nexo platform.</p>
        <a href="https://www.example.com" target="_blank" class="btn btn-primary mt-3" role="button" aria-label="Initiate your request on the Nexo platform">Initiate Request</a> <!-- Dark Green Button -->
        <hr class="my-4" style="border-top: 1px solid dimgrey;">
        <p class="mb-0 text-dark">We hope you have an amazing time at the events!</p>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-0">Events This Week</h1>
            @if($canFilterMyVenues)
                <style>
                    /* Green switch toggle */
                    #filterMyVenues:checked {
                        background-color: #28a745;
                        border-color: #28a745;
                    }
                </style>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="filterMyVenues" wire:click="toggleFilterMyVenues" @checked($filterMyVenues)>
                    <label class="form-check-label" for="filterMyVenues">Filter By My Venues</label>
                </div>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group">
                <button class="btn btn-primary btn-sm" wire:click="goWeek('prev')" aria-label="Previous week">&laquo; Previous</button>
                <span class="btn btn-primary btn-sm disabled">{{ $weekLabel }}</span>
                <button class="btn btn-primary btn-sm" wire:click="goWeek('next')" aria-label="Next week">Next &raquo;</button>
            </div>
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
                                <button class="btn btn-link p-0 text-start text-primary flex-grow-1 text-truncate"
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

    <!------------------------------------------------------------------------------------------------------------>
    <!--------------------------------------------Modals---------------------------------------------------------->
    <!------------------------------------------------------------------------------------------------------------>

    <!-- Event details modal (generic) -->
    <div class="modal fade" id="eventDetails" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-event me-2"></i>{{ $modal['event']->title ?? 'Event' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Venue Information (assuming you have venue relationship set up) -->
                    <div class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        {{ $modal['event']->venue->code ?? 'No venue' }} <!-- If you have a venue relation -->
                    </div>

                    <!-- Event Time -->
                    <div class="mb-3">
                        <i class="bi bi-clock me-2"></i>
                        @if(isset($modal['event']) && $modal['event']->start_time && $modal['event']->end_time)
                            {{ \Carbon\Carbon::parse($modal['event']->start_time)->format('D, M j • g:ia') }} —
                            {{ \Carbon\Carbon::parse($modal['event']->end_time)->format('g:ia') }}
                        @else
                            <span class="text-muted">Time not available</span>
                        @endif
                    </div>

                    <!-- Organization Name -->
                    <div class="mb-3">
                        <i class="bi bi-person-workspace me-2"></i>
                        Organized by: {{ $modal['event']->organization_name ?? 'N/A' }}
                    </div>

                    <!-- Event Description -->
                    <label class="mb-2 fw-semibold">Event Description:</label>
                    <p class="mb-0">{{ $modal['event']->description ?? 'No description provided' }}</p>
                </div>
                <div class="modal-footer d-flex justify-content-between">
{{--                    <button class="btn btn-primary" aria-label="View more details" onclick="{{route('approver.history.request')}}">View More Details</button>--}}
                    <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close details">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approvers -->
    <div class="modal fade" id="eventDetailsApprover" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-event me-2"></i>Approver: {{ $modal['event']->title ?? 'Event' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Venue Information (assuming you have venue relationship set up) -->
                    <div class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        {{ $modal['event']->venue->code ?? 'No venue' }} <!-- If you have a venue relation -->
                    </div>

                    <!-- Event Time -->
                    <div class="mb-3">
                        <i class="bi bi-clock me-2"></i>
                        @if(isset($modal['event']) && $modal['event']->start_time && $modal['event']->end_time)
                            {{ \Carbon\Carbon::parse($modal['event']->start_time)->format('D, M j • g:ia') }} —
                            {{ \Carbon\Carbon::parse($modal['event']->end_time)->format('g:ia') }}
                        @else
                            <span class="text-muted">Time not available</span>
                        @endif
                    </div>

                    <!-- Organization Name -->
                    <div class="mb-3">
                        <i class="bi bi-person-workspace me-2"></i>
                        Organized by: {{ $modal['event']->organization_name ?? 'N/A' }}
                    </div>

                    <!-- Event Description -->
                    <label class="mb-2 fw-semibold">Event Description:</label>
                    <p class="mb-0">{{ $modal['event']->description ?? 'No description provided' }}</p>
{{--                    <label class="mb-2 fw-semibold">Requester:</label>--}}
                    <div ><span class="mb-2 fw-semibold">Requester: </span>{{ $modal['event']->requester->first_name ?? '' }} {{$modal['event']->requester->last_name ?? ''}} </div>


{{--                    <section class="card shadow-sm mb-4" aria-labelledby="event-description">--}}
{{--                        <div class="card-body">--}}
{{--                            <h3 id="event-description" class="fw-semibold mb-2">Description</h3>--}}
{{--                            <p class="mb-1"><strong>Guest Volume:</strong> {{ $modal['event']->guest_size ?? 'N/A' }}</p>--}}
{{--                            <p class="mb-0">{{ $modal['event']->description ?? 'No description provided' }}}}</p>--}}
{{--                        </div>--}}
{{--                    </section>--}}

                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button class="btn btn-primary" aria-label="View more details">View More Details</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close details">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Event View Modal (moved outside) -->
    <div class="modal fade" id="publicEventDetails" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(isset($modal['event']) && $modal['event'])
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Title</label><input
                                    class="form-control" readonly value="{{ $modal['event']->title ?? '' }}">
                            </div>
                            <div class="col-md-3"><label class="form-label">Organization</label><input
                                    class="form-control" readonly
                                    value="{{ $modal['event']->organization_name ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label">Venue</label><input
                                    class="form-control" readonly
                                    value="{{ optional($modal['event']->venue)->code ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label">From</label><input
                                    class="form-control" readonly
                                    value="{{ $modal['event']->start_time ? \Carbon\Carbon::parse($modal['event']->start_time)->format('D, M j, Y g:i A') : '' }}">
                            </div>
                            <div class="col-md-3"><label class="form-label">To</label><input
                                    class="form-control" readonly
                                    value="{{ $modal['event']->end_time ? \Carbon\Carbon::parse($modal['event']->end_time)->format('D, M j, Y g:i A') : '' }}">
                            </div>
                            <div class="col-md-3"><label class="form-label">Status</label><input
                                    class="form-control" readonly value="{{ $modal['event']->status ?? '' }}">
                            </div>
                            <div class="col-md-3"><label class="form-label">Created At</label><input
                                    class="form-control" readonly
                                    value="{{ optional($modal['event']->created_at)->format('D, M j, Y g:i A') }}"></div>
                            <div class="col-md-3"><label class="form-label">Updated At</label><input
                                    class="form-control" readonly
                                    value="{{ optional($modal['event']->updated_at)->format('D, M j, Y g:i A') }}"></div>
                            <div class="col-12"><label class="form-label">Description</label><textarea
                                    class="form-control" rows="3"
                                    readonly>{{ $modal['event']->description ?? '' }}</textarea></div>
                        </div>
                    @else
                        <div class="alert alert-warning">No event details available.</div>
                    @endif
                </div>

                {{-- Documents --}}
                <div class="container">
                <section class="card shadow-sm mb-4" aria-labelledby="event-documents">
                    <div class="card-body">
                        <h3 id="event-documents" class="fw-semibold border-bottom pb-2 mb-3">Documents</h3>

                            <livewire:documents.list-with-preview     :docs="$docs"
                                                                  :key="'docs-'.($modal['event']->id ?? '0')" />
                        </div>


                </section>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal"
                            aria-label="Close details">Close</button>
                </div>
            </div>
        </div>
    </div>


</div>

<script>
    (function () {
        function ensureBodyScrollable() {
            try {
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
                setTimeout(ensureBodyScrollable, 0);
            });
        });
    })();
</script>
