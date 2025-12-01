<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('public.calendar') ? 'active' : '' }}" href="{{ route('public.calendar') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.pending.index') ? 'active' : '' }}" href="{{ route('approver.pending.index') }}">Pending Request</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.history.index') ? 'active' : '' }}" href="{{ route('approver.history.index') }}">Request History</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('venues.manage') ? 'active' : '' }}" href="{{ route('venues.manage') }}">My Venues</a>
        </li>
    </ul>
</x-slot:pageActions>

<div class="container">

    {{-- Header: title + back button on same row (mobile & desktop) --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">Venue Details</h1>

        <a href="{{ route('venues.manage') }}"
           class="btn btn-secondary ms-auto"
           onclick="if (history.length > 1 && document.referrer?.startsWith(location.origin)) { history.back(); return false; }">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    {{-- Details card --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Code:</dt>
                <dd class="col-sm-8">{{ $venue->code }}</dd>

                <dt class="col-sm-4">Name:</dt>
                <dd class="col-sm-8">{{ $venue->name }}</dd>

                <dt class="col-sm-4">Department:</dt>
                <dd class="col-sm-8">{{ $venue->department->name ?? '—' }}</dd>

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

                <dt class="col-sm-4">Use Requirements:</dt>
                <dd class="col-sm-8">
                    @if(!empty($requirements))
                        <div class="vstack gap-3">
                            @foreach($requirements as $requirement)
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <p class="fw-semibold mb-1">{{ $requirement['name'] }}</p>
                                            @if(!empty($requirement['description']))
                                                <p class="mb-1 text-muted">{{ $requirement['description'] }}</p>
                                            @endif
                                        </div>
                                        @if(!empty($requirement['hyperlink']))
                                            <a href="{{ $requirement['hyperlink'] }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="btn btn-sm btn-primary">
                                                View Document
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span>—</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</div>
