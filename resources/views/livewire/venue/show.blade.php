
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
            <a class="fw-bold nav-link {{ Route::is('public.calendar') ? 'active' : '' }}" href="{{ route('public.calendar') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link  {{ Route::is('approver.pending.index') ? 'active' : '' }}" href="{{ route('approver.pending.index') }}">Pending Request</a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.history.index') ? 'active' : '' }} " href="{{ route('approver.history.index') }}">Request History</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('venues.manage') }}">My Venues</a>
        </li>

    </ul>

</x-slot:pageActions>

<div>


<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Venue Details</h1>

    <div class="d-flex gap-2">
        {{-- Back link with safe fallback to venues.index --}}
        <a href="{{ route('venues.manage') }}"
           class="btn btn-secondary"
           onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
            Back
        </a>

{{--        --}}{{-- Optional: link to your existing Configure screen for this venue --}}
{{--        <a href="{{ route('home', $venue) }}" class="btn btn-primary" @if (function_exists('wire')) wire:navigate @endif>--}}
{{--            <i class="bi bi-pencil-square"></i> Edit--}}
{{--        </a>--}}
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

{{--            <dt class="col-sm-4">Current Manager:</dt>--}}
{{--            <dd class="col-sm-8">{{ trim(($venue->manager->first_name ?? '').' '.($venue->manager->last_name ?? '')) ?: '—' }}</dd>--}}

            <dt class="col-sm-4">Capacity:</dt>
            <dd class="col-sm-8">{{ $venue->capacity ? number_format($venue->capacity) : '—' }}</dd>

            <dt class="col-sm-4">Description:</dt>
            <dd class="col-sm-8">
                {{ $venue->description ? $venue->description : '—' }}
            </dd>

            <dt class="col-sm-4">Weekly Availability:</dt>
            <dd class="col-sm-8">
                @if (!empty($schedule))
                    <ul class="list-unstyled mb-0">
                        @foreach ($schedule as $slot)
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span class="fw-semibold">{{ $slot['day'] }}</span>
                                <span>{{ $slot['opens'] }} – {{ $slot['closes'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>—</span>
                @endif
            </dd>

            <dt class="col-sm-4">Features:</dt>
            <dd class="col-sm-8">
                @if($venue->getFeatures() && count($venue->getFeatures()) > 0)
                    <ul class="list-unstyled">
                        @foreach($venue->getFeatures() as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                @else
                    <span>—</span>
                @endif
            </dd>

        </dl>
    </div>
</div>
</div>
