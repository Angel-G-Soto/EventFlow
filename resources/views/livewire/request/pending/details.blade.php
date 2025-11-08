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
    <section class="card shadow-sm mb-4" aria-labelledby="event-header">
        <div class="card-body">
            <h2 id="event-header" class="fw-semibold mb-2">{{ $event->title }}</h2>
            <p class="text-muted mb-1">
                {{ $start->format('M j, Y') }}: {{ $start->format('g:i A') }} – {{ $end->format('g:i A') }}
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
