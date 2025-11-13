
{{--    View: Venue Details--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Presents an individual venue with fields: Name, Department, Current Manager,--}}
{{--      Capacity, Opening Time, and Closing Time.--}}
{{--    - Receives a typed $venue model from the Livewire component.--}}

{{--    Variables:--}}
{{--    @var \App\Models\Venue $venue--}}

{{--    Accessibility notes:--}}
{{--    - Use a semantic definition list (<dl>) or table with <th scope> for label/value pairs.--}}
{{--    - Ensure time values are localized and have clear labels (e.g., aria-label).--}}
{{--    - Buttons/links must include discernible text for screen readers.--}}

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
            <p class="mb-1"><strong>Guest Size:</strong> {{ $event->guest_size ?? 'N/A' }}</p>
            <p class="mb-0">{{ $event->description }}</p>
        </div>
    </section>

    @if($event->venue)
        <section class="card shadow-sm mb-4" aria-labelledby="venue-details">
            <div class="card-body">
                <h3 id="venue-details" class="fw-semibold border-bottom pb-2 mb-3">Venue Details</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $event->venue->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Code</dt>
                    <dd class="col-sm-8">{{ $event->venue->code ?? '—' }}</dd>

                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $event->venue->description ?? 'No description available.' }}</dd>
                </dl>
            </div>
        </section>
    @endif

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

    {{-- Categories --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-categories">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 id="event-categories" class="fw-semibold mb-0">Categories</h3>
                @if($event->categories->isNotEmpty())
                    <small class="text-muted">{{ $event->categories->count() }} selected</small>
                @endif
            </div>
            @if($event->categories->isEmpty())
                <p class="text-muted mb-0">No categories were associated with this event.</p>
            @else
                <ul class="mb-0 ps-3">
                    @foreach($event->categories as $category)
                        <li class="mb-2">
                            <span class="fw-semibold d-block">{{ $category->name }}</span>
                            @if(!empty($category->description))
                                <small class="text-muted">{{ $category->description }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Documents --}}
    <section class="card shadow-sm mb-4" aria-labelledby="event-documents">
        <div class="card-body">
            <h3 id="event-documents" class="fw-semibold border-bottom pb-2 mb-3">Documents</h3>
            <livewire:documents.list-with-preview :docs="$docs" />
        </div>
    </section>

    {{--Buttons--}}
    <div class="d-flex gap-2 mb-3 container-fluid">

        @if($event->status === 'approved')
            <button type="button" class="btn btn-outline-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
                Cancel
            </button>
        @elseif(str_contains($event->status,'pending'))
            <button type="button" class="btn btn-outline-danger d-flex" data-bs-toggle="modal" data-bs-target="#denyModal">
                Withdraw
            </button>
        @endif

        <button type="button" wire:click="back" class="btn btn-outline-secondary ms-auto"
                wire:target="back">
            Back
        </button>

    </div>


    <div class="modal fade"
         id="denyModal"
         tabindex="-1" aria-hidden="true"
         wire:ignore.self
         wire:key="deny-modal-{{ $event->id ?? 'single' }}"
         x-data="{ justification: @entangle('justification') }">

        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Write a message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
        <textarea class="form-control"
                  x-model="justification"
                  rows="4" required minlength="10"
                  placeholder="Type at least 10 characters..."></textarea>
                </div>

                <div class="modal-footer">
                    @if($event->status === 'approved')
                        <button class="btn btn-outline-danger"
                                wire:click="save"
                                :disabled="justification.trim().length < 10"
                                wire:loading.attr="disabled" wire:target="save">
                            Cancel Request
                        </button>
                    @elseif(str_contains($event->status,'pending'))

                        <button class="btn btn-outline-danger"
                                wire:click="save"
                                :disabled="justification.trim().length < 10"
                                wire:loading.attr="disabled" wire:target="save">
                            Withdraw Request
                        </button>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
