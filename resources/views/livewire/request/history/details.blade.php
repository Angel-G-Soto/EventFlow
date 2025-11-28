{{-- View: Event Details --}}
{{-- Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5) --}}
{{-- Date: 2025-11-08 --}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('public.calendar') ? 'active' : '' }}"
               href="{{ route('public.calendar') }}">
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.history.index') ? 'active' : '' }}"
               href="{{ route('approver.history.index') }}">
                Request History
            </a>
        </li>
    </ul>
</x-slot:pageActions>

@php
    $start = \Carbon\Carbon::parse($eventHistory->event->start_time);
    $end = \Carbon\Carbon::parse($eventHistory->event->end_time);
@endphp

<div>
    <div class="container my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <h1 class="fw-bold mb-0">Approval Details</h1>
            <div class="d-flex flex-column flex-sm-row gap-2 ms-md-auto">
                <button type="button"
                        wire:click="back"
                        class="btn btn-secondary"
                        wire:target="back"
                        aria-label="Go Back">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>
            </div>
        </div>

        {{-- Approver Action & Comment --}}
{{--        @if($eventHistory->status_when_signed && in_array(strtolower($eventHistory->status_when_signed), ['denied', 'cancelled']))--}}
            <section class="card shadow-sm mb-4" aria-labelledby="approver-action">
                <div class="card-body">
                    <h3 id="approver-action" class="fw-semibold border-bottom pb-2 mb-3">Approver Action</h3>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Action Taken</dt>
                        <dd class="col-sm-8 text-capitalize">{{ $eventHistory->action }}</dd>

                        <dt class="col-sm-4">Approver Comment</dt>
                        <dd class="col-sm-8">{{ $eventHistory->comment ?? 'No comment provided.' }}</dd>

                        <dt class="col-sm-4">Approval Type</dt>
                        <dd class="col-sm-8">
                            {{$eventHistory->getSimpleStatus()}}
                        </dd>
                    </dl>
                </div>
            </section>
{{--        @endif--}}

        <style>
            .status-indicator {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-weight: 600;
                font-size: 0.95rem;
            }

            .status-indicator .status-dot {
                width: 0.45rem;
                height: 0.45rem;
                border-radius: 50%;
                display: inline-block;
                background-color: currentColor;
            }

            .status-indicator--success {
                color: #146c43;
            }

            .status-indicator--danger {
                color: #b02a37;
            }

            .status-indicator--neutral {
                color: #856404;
            }
        </style>

        <h1 class="fw-bold mb-3">Event Details</h1>
        {{-- Event Header --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-header">
            <div class="card-body">
                <h3 id="event-header" class="fw-semibold mb-1">{{ $eventHistory->event->title }}</h3>
                <p class="text-muted mb-1">
                    {{ $start->format('M j, Y') }}: {{ $start->format('g:i A') }} – {{ $end->format('g:i A') }}
                </p>
                @php
                    $detailStatusVariant = match (true) {
                        in_array($eventHistory->event->status, ['cancelled', 'withdrawn', 'rejected']) => 'danger',
                        in_array($eventHistory->event->status, ['approved', 'completed']) => 'success',
                        default => 'neutral',
                    };
                @endphp
                <span class="status-indicator status-indicator--{{ $detailStatusVariant }}" aria-label="Event Status: {{ $eventHistory->event->getSimpleStatus() }}">
                    <span class="status-dot" aria-hidden="true"></span>
                    <span>{{ 'Current Status: '. $eventHistory->event->getSimpleStatus() }}</span>
                </span>

                @if($terminalNotice)
                    <div class="ps-3 py-2 my-2 bg-light rounded">
                        <p class="mb-1 fw-semibold">
                            {{ ucfirst($terminalNotice['action'] ?? '') }} by {{ $terminalNotice['actor_name'] ?? 'Unknown user' }}
                            @if(!empty($terminalNotice['actor_email']))
                                <span class="text-muted small ms-1">({{ $terminalNotice['actor_email'] }})</span>
                            @endif
                        </p>
                        <p class="mb-0">
                            <strong>Justification:</strong>
                            {{ $terminalNotice['comment'] ?: 'No justification provided.' }}
                        </p>
                    </div>
                @endif

                @if($eventHistory->event->status === 'approved')
                    <div class="mt-3">
                        <button type="button"
                                class="btn btn-primary"
                                wire:click="downloadSummary"
                                wire:loading.attr="disabled"
                                wire:target="downloadSummary">
                            <span wire:loading.remove wire:target="downloadSummary">Download Request PDF</span>
                            <span wire:loading wire:target="downloadSummary">Preparing...</span>
                        </button>
                    </div>
                @endif
            </div>
        </section>

        {{-- Description & Guest Size Inline --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-description">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h4 id="event-description" class="fw-semibold mb-0 me-2">Description</h4>
                </div>
                <p class="mb-0 text-justify"><strong>Guest Volume:</strong> {{ $eventHistory->event->guest_size }}</p>
                <p class="mb-0 text-justify">{{ $eventHistory->event->description }}</p>
            </div>
        </section>

        @if($eventHistory->event->venue)
            <section class="card shadow-sm mb-4" aria-labelledby="venue-details">
                <div class="card-body">
                    <h4 id="venue-details" class="fw-semibold border-bottom pb-2 mb-3">Venue Details</h4>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $eventHistory->event->venue->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Code</dt>
                        <dd class="col-sm-8">{{ $eventHistory->event->venue->code ?? '—' }}</dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $eventHistory->event->venue->description ?? 'No description available.' }}</dd>
                    </dl>
                </div>
            </section>
        @endif


        {{-- Organization & Requester Info --}}
        <section class="card shadow-sm mb-4" aria-labelledby="organization-info">
            <div class="card-body">
                <h4 id="organization-info" class="fw-semibold border-bottom pb-2 mb-3">Organization & Requester</h4>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Requester</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:{{ $eventHistory->event->requester->email }}">
                            {{ $eventHistory->event->requester->first_name }} {{ $eventHistory->event->requester->last_name }}
                        </a>
                    </dd>

                    <dt class="col-sm-4">Organization</dt>
                    <dd class="col-sm-8">{{ $eventHistory->event->organization_name }}</dd>

                <dt class="col-sm-4">Advisor</dt>
                <dd class="col-sm-8">
                    <a href="mailto:{{ $eventHistory->event->organization_advisor_email }}">
                        {{ $eventHistory->event->organization_advisor_name }}
                    </a>
                </dd>
                <dt class="col-sm-4">Advisor Phone</dt>
                <dd class="col-sm-8">
                    @if($eventHistory->event->organization_advisor_phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $eventHistory->event->organization_advisor_phone) }}">
                            {{ $eventHistory->event->organization_advisor_phone }}
                        </a>
                    @else
                        —
                    @endif
                </dd>

                    <dt class="col-sm-4">Date Submitted</dt>
                    <dd class="col-sm-8">{{ $eventHistory->event->created_at->format('D, M j, Y g:i A') }}</dd>
                </dl>
            </div>
        </section>

        {{-- Event Attributes --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-attributes">
            <div class="card-body">
                <h4 id="event-attributes" class="fw-semibold border-bottom pb-2 mb-3">Event Attributes</h4>
                <div class="d-flex flex-column gap-2">
                    <div>
                        <span class="fw-semibold me-2">Handles Food:</span>
                        <span class="status-indicator status-indicator--{{ $eventHistory->event->handles_food ? 'success' : 'danger' }}"
                              aria-label="Handles Food: {{ $eventHistory->event->handles_food ? 'Yes' : 'No' }}">
                            <span class="status-dot" aria-hidden="true"></span>
                            <span>{{ $eventHistory->event->handles_food ? 'Yes' : 'No' }}</span>
                        </span>
                    </div>

                    <div>
                        <span class="fw-semibold me-2">Uses Institutional Funds:</span>
                        <span class="status-indicator status-indicator--{{ $eventHistory->event->use_institutional_funds ? 'success' : 'danger' }}"
                              aria-label="Uses Institutional Funds: {{ $eventHistory->event->use_institutional_funds ? 'Yes' : 'No' }}">
                            <span class="status-dot" aria-hidden="true"></span>
                            <span>{{ $eventHistory->event->use_institutional_funds ? 'Yes' : 'No' }}</span>
                        </span>
                    </div>

                    <div>
                        <span class="fw-semibold me-2">Invites External Guests:</span>
                        <span class="status-indicator status-indicator--{{ $eventHistory->event->external_guest ? 'success' : 'danger' }}"
                              aria-label="Invites External Guests: {{ $eventHistory->event->external_guest ? 'Yes' : 'No' }}">
                            <span class="status-dot" aria-hidden="true"></span>
                            <span>{{ $eventHistory->event->external_guest ? 'Yes' : 'No' }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Categories --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-categories">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 id="event-categories" class="fw-semibold mb-0">Categories</h4>
                    @if($eventHistory->event->categories->isNotEmpty())
                        <small class="text-muted">{{ $eventHistory->event->categories->count() }} selected</small>
                    @endif
                </div>
                @if($eventHistory->event->categories->isEmpty())
                    <p class="text-muted mb-0">No categories were associated with this event.</p>
                @else
                    <ul class="mb-0 ps-3">
                        @foreach($eventHistory->event->categories as $category)
                            <li class="mb-2">
                                <span class="fw-semibold d-block">{{ $category->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>


        {{-- Documents --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-documents">
            <div class="card-body">
                <h4 id="event-documents" class="fw-semibold border-bottom pb-2 mb-3">Documents</h4>
                <livewire:documents.list-with-preview :docs="$docs" />
            </div>
        </section>

        {{-- Action Buttons --}}
        @if(strtolower($eventHistory->action)==='approved')
            <div class="d-flex gap-2 mb-5">
                <button type="button"
                        class="btn btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#denyModal"
                        aria-label="Cancel Request Approval">
                    <i class="bi bi-x-circle"></i>
                    Cancel Request Approval
                </button>
            </div>
        @endif
    </div>

    {{-- Deny Modal --}}
    <div class="modal fade"
         id="denyModal"
         tabindex="-1"
         aria-labelledby="denyModalLabel"
         aria-hidden="true"
         wire:ignore.self
         wire:key="deny-modal-{{ $eventHistory->event->id ?? 'single' }}"
         x-data="{ justification: @entangle('justification') }">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 id="denyModalLabel" class="modal-title">Write a message</h6>
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
                            aria-label="Submit Cancel Request Approval">
                        Cancel Request Approval
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
