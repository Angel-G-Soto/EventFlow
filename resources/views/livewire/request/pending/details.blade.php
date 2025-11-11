{{-- View: Event Details --}}
{{-- Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5) --}}
{{-- Date: 2025-11-08 --}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('public.calendar') ? 'active' : '' }}"
               href="{{ route('public.calendar') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.pending.index') ? 'active' : '' }}"
               href="{{ route('approver.pending.index') }}">Pending Request</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.history.index') ? 'active' : '' }}"
               href="{{ route('approver.history.index') }}">Request History</a>
        </li>
    </ul>
</x-slot:pageActions>

@php
    $start = \Carbon\Carbon::parse($event->start_time);
    $end = \Carbon\Carbon::parse($event->end_time);
@endphp

<div class="container my-4">
    {{-- Conflicting Events Alert --}}
    @if($conflicts->count() > 0 && in_array('venue-manager', \Illuminate\Support\Facades\Auth::user()->roles->pluck('name')->toArray()) && str_contains($event->status, 'venue manager'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h4 class="alert-heading">Conflict(s) Detected!</h4>
            <p>There are conflicts with this event. Please review the following conflicting events:</p>
            <ul class="list-group">
                @foreach($conflicts as $conflict)
                    <li class="list-group-item">
                        @if(str_contains($conflict['status'], 'venue manager'))
                            {{-- For Pending, Venue Manager, or DSCA events --}}
                            <a target="_blank" href="{{ route('approver.pending.request', $conflict['id']) }}" class="fw-semibold">
                                {{ $conflict['title'] }}  | Status: {{$conflict['status']}}
                            </a>
                        @else
                            {{-- For Approved Events --}}
                            @php
                                // Get the latest event history where the current user is the approver
                                $lastApproval = $conflict->history()
                                    ->where('approver_id', Auth::id()) // assuming the approver is linked to 'approver_id'
                                    ->where('event_id', $conflict['id'])
                                    ->latest() // Get the most recent history
                                    ->first();
                            @endphp
                            <a target="_blank" href="{{ route('approver.history.request', $lastApproval->id) }}" class="fw-semibold">
                                {{ $conflict['title'] }}  | Status: {{$conflict['status']}}
                            </a>
                        <br>
                        <small>
                            Conflicts from {{ \Carbon\Carbon::parse($conflict['start_time'])->format('M j, Y g:i A') }} to {{ \Carbon\Carbon::parse($conflict['end_time'])->format('g:i A') }}
                        </small>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Pagination Links with Red Background --}}
            <div class="d-flex justify-content-end mt-3">
                {{ $conflicts->links('pagination::bootstrap-5') }}
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif


    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h1 class="fw-bold">Event Details</h1>
        <button type="button"
                wire:click="back"
                class="btn btn-secondary ms-auto"
                wire:target="back"
                aria-label="Go Back">
            Back
        </button>
    </div>

    {{-- Event Header --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-header" role="region">
        <div class="card-body">
            <h2 id="event-header" class="fw-semibold mb-2">{{ $event->title }}</h2>

            <!-- Status as a tag (badge) with appropriate styles and ARIA labeling -->
            <p>
                <span class="badge bg-secondary" aria-label="Event Status: {{ $event->getSimpleStatus() }}">
                    {{ 'Status: '. $event->getSimpleStatus() }}
                </span>
            </p>

            <p class="text-muted mb-1">
                <span class="sr-only">Event Start Date: </span>{{ $start->format('M j, Y') }}
                at <span class="sr-only">From: </span>{{ $start->format('g:i A') }} –
                <span class="sr-only">To: </span>{{ $end->format('g:i A') }}
            </p>
        </div>
    </section>

    {{-- Description & Guest Size --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-description">
        <div class="card-body">
            <h3 id="event-description" class="fw-semibold mb-2">Description</h3>
            <p class="mb-1"><strong>Guest Volume:</strong> {{ $event->guest_size ?? 'N/A' }}</p>
            <p class="mb-0">{{ $event->description }}</p>
        </div>
    </section>

    {{-- Organization & Requester Info --}}
    <section class="card shadow-sm mb-4" aria-labelledby="organization-info">
        <div class="card-body">
            <h3 id="organization-info" class="fw-semibold border-bottom pb-2 mb-3">Organization & Requester</h3>
            <dl class="row mb-0">
                <dt class="col-sm-4">Requester</dt>
                <dd class="col-sm-8">
                    <a href="mailto:{{ $event->requester->email }}">
                        {{ $event->requester->first_name }} {{ $event->requester->last_name }}
                    </a>
                </dd>

                <dt class="col-sm-4">Organization</dt>
                <dd class="col-sm-8">{{ $event->organization_name }}</dd>

                <dt class="col-sm-4">Advisor</dt>
                <dd class="col-sm-8">
                    <a href="mailto:{{ $event->organization_advisor_email }}">
                        {{ $event->organization_advisor_name }}
                    </a>
                </dd>

                <dt class="col-sm-4">Date Submitted</dt>
                <dd class="col-sm-8">{{ $event->created_at->format('M j, Y g:i A') }}</dd>
            </dl>
        </div>
    </section>

    {{-- Event Attributes --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-attributes">
        <div class="card-body">
            <h3 id="event-attributes" class="fw-semibold border-bottom pb-2 mb-3">Event Attributes</h3>
            <div class="d-flex flex-column gap-2">
                <div>
                    <span class="fw-semibold me-2">Handles Food:</span>
                    <span class="badge bg-{{ $event->handles_food ? 'success' : 'secondary' }}">
                        {{ $event->handles_food ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div>
                    <span class="fw-semibold me-2">Uses Institutional Funds:</span>
                    <span class="badge bg-{{ $event->use_institutional_funds ? 'success' : 'secondary' }}">
                        {{ $event->use_institutional_funds ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div>
                    <span class="fw-semibold me-2">Invites External Guests:</span>
                    <span class="badge bg-{{ $event->external_guests ? 'success' : 'secondary' }}">
                        {{ $event->external_guests ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- Documents --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-documents">
        <div class="card-body">
            <h3 id="event-documents" class="fw-semibold border-bottom pb-2 mb-3">Documents</h3>
            <livewire:documents.list-with-preview :docs="$docs" />
        </div>
    </section>

    {{-- Action Buttons --}}
    <div class="d-flex gap-2 mb-5">
        <button type="button" wire:click="approve" class="btn btn-success" wire:target="approve">
            Approve
        </button>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#denyModal">
            Reject
        </button>
    </div>

    {{-- Deny Modal --}}
    <div class="modal fade"
         id="denyModal"
         tabindex="-1"
         aria-labelledby="denyModalLabel"
         aria-hidden="true"
         wire:ignore.self
         wire:key="deny-modal-{{ $event->id ?? 'single' }}"
         x-data="{ justification: @entangle('justification') }">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="denyModalLabel" class="modal-title">Write a message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <textarea class="form-control"
                              x-model="justification"
                              rows="4"
                              required minlength="10"
                              aria-label="Justification message"
                              placeholder="Type at least 10 characters..."></textarea>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-danger"
                            wire:click="save"
                            :disabled="justification.trim().length < 10"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            aria-label="Submit Reject Request">
                        Reject Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
