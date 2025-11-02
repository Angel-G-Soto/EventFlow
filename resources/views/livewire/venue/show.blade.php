
{{--    View: Show (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Presents a single record with labeled fields and actions (if any).--}}
{{--    - Receives a typed model/array from the Livewire component.--}}

{{--    Variables (typical):--}}
{{--    @var object|array|\App\Models\Event|\App\Models\Venue $record--}}

{{--    Accessibility notes:--}}
{{--    - Prefer a semantic <dl> for label/value pairs or a table with <th scope>.--}}
{{--    - Ensure action buttons/links include discernible text and keyboard focus styles.--}}
{{--    - Localize dates/times; include <time datetime="..."> for machine-readable values.--}}


{{-- resources/views/livewire/venue/show.blade.php --}}

{{-- Page header with Back + Edit actions --}}
<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.requests.pending') }}">Pending Request</a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">Request History</a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">My Venues</a>
        </li>

    </ul>

</x-slot:pageActions>

<div>


<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Venue Details</h1>

    <div class="d-flex gap-2">
        {{-- Back link with safe fallback to venues.index --}}
        <a href="{{ route('home') }}"
           class="btn btn-outline-secondary"
           onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        {{-- Optional: link to your existing Configure screen for this venue --}}
        <a href="{{ route('home', $venue) }}" class="btn btn-primary" @if (function_exists('wire')) wire:navigate @endif>
            <i class="bi bi-pencil-square"></i> Edit
        </a>
    </div>
</div>

{{-- Details card --}}
<div class="card shadow-sm">
    <div class="card-body">
        {{-- Definition list provides good semantics for “label : value” layouts --}}
        <dl class="row mb-0">
            <dt class="col-sm-4">Name:</dt>
            <dd class="col-sm-8">{{ $venue->name }}</dd>

            <dt class="col-sm-4">Department:</dt>
            <dd class="col-sm-8">{{ $venue->department->name ?? '—' }}</dd>

            <dt class="col-sm-4">Current Manager:</dt>
            <dd class="col-sm-8">{{ trim(($venue->manager->first_name ?? '').' '.($venue->manager->last_name ?? '')) ?: '—' }}</dd>

            <dt class="col-sm-4">Capacity:</dt>
            <dd class="col-sm-8">{{ $venue->capacity ? number_format($venue->capacity) : '—' }}</dd>

            <dt class="col-sm-4">Opening Time:</dt>
            <dd class="col-sm-8">{{ $open ?? '—' }}</dd>

            <dt class="col-sm-4">Closing Time:</dt>
            <dd class="col-sm-8">{{ $close ?? '—' }}</dd>
        </dl>
    </div>
</div>
</div>
