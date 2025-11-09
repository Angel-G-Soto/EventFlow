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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <h1 class="fw-bold mb-4">Approval Details</h1>
            <button type="button"
                    wire:click="back"
                    class="btn btn-secondary ms-auto"
                    wire:target="back"
                    aria-label="Go Back">
                Back
            </button>
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
                            @if(str_contains($eventHistory->status_when_signed, 'venue manager'))
                                Venue Manager
                            @elseif(str_contains($eventHistory->status_when_signed, 'dsca'))
                                Event Approver (DSCA)
                            @elseif(str_contains($eventHistory->status_when_signed, 'advisor'))
                                Advisor
                            @else
                                {{ $eventHistory->status_when_signed ?? 'No comment provided.' }}
                            @endif
                        </dd>
                    </dl>
                </div>
            </section>
{{--        @endif--}}

        {{-- Event Header --}}
        <h1 class="fw-bold mb-4">Event Details</h1>

        <section class="card shadow-sm mb-4" aria-labelledby="event-header">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                    <div>
                        <h3 id="event-header" class="fw-semibold mb-1">{{ $eventHistory->event->title }}</h3>
                        <p class="text-muted mb-1">
                            {{ $start->format('M j, Y') }}: {{ $start->format('g:i A') }} – {{ $end->format('g:i A') }}
                        </p>
                        @php
                            $statusLower = strtolower($eventHistory->status_when_signed);

                            if (str_contains($statusLower, 'pending')) {
                                $display = 'Pending Approval';
                            } else {
                                $display = $eventHistory->status_when_signed;
                            }
                        @endphp
                        <span class="badge rounded-pill text-bg-secondary">Current Status: {{ $display }}</span>
                    </div>
                </div>
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
                    <dd class="col-sm-8">{{ $eventHistory->event->organization_nexo_name }}</dd>

                    <dt class="col-sm-4">Advisor</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:{{ $eventHistory->event->organization_advisor_email }}">
                            {{ $eventHistory->event->organization_advisor_name }}
                        </a>
                    </dd>

                    <dt class="col-sm-4">Date Submitted</dt>
                    <dd class="col-sm-8">{{ $eventHistory->event->created_at->format('M j, Y g:i A') }}</dd>
                </dl>
            </div>
        </section>

        {{-- Event Attributes --}}
        <section class="card shadow-sm mb-4" aria-labelledby="event-attributes">
            <div class="card-body">
                <h4 id="event-attributes" class="fw-semibold border-bottom pb-2 mb-3">Event Attributes</h4>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-semibold mb-1">Handles Food:
                            <span class="badge bg-{{ $eventHistory->event->handles_food ? 'success' : 'secondary' }}"
                                  aria-label="Handles Food: {{ $eventHistory->event->handles_food ? 'Yes' : 'No' }}">{{ $eventHistory->event->handles_food ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>

                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-semibold mb-1">Uses Institutional Funds:
                            <span class="badge bg-{{ $eventHistory->event->use_institutional_funds ? 'success' : 'secondary' }}"
                                  aria-label="Uses Institutional Funds: {{ $eventHistory->event->use_institutional_funds ? 'Yes' : 'No' }}"> {{ $eventHistory->event->use_institutional_funds ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>

                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-semibold mb-1">Invites External Guests:
                            <span class="badge bg-{{ $eventHistory->event->external_guests ? 'success' : 'secondary' }}" aria-label="Invites External Guests: {{ $eventHistory->event->external_guests ? 'Yes' : 'No' }}">
                                {{ $eventHistory->event->external_guests ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>
                </div>
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
