<x-layouts.header.public>
    <div>
        <h1 class="h4 mb-3">Pending Requests</h1>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label">Student Organization</label>
                        <input class="form-control" placeholder="e.g. CAHSI">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Event Type</label>
                        <input class="form-control" >
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Venue</label>
                        <input class="form-control">
                    </div>

                </div>
            </div>
        </div>
        <ul class="list-group shadow-sm">
            @foreach ($events as $event)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        {{-- Simple initial avatar --}}
                        <div>
                            <div class="fw-bold">
                                <span>{{ $event->e_title ?? '—' }}</span>
                            </div>
                            <div class="small text-muted">Organization: {{ $event->organization_nexo_name }}</div>
                            <div class="small text-muted">Date submitted: {{ $event->created_at}}</div>
                        </div>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-primary d-flex" onclick="window.location='{{ route('approver.requests',['id'=>$event->event_id]) }}'">
                            View Details
                        </button>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-3">
            {{ $events->withQueryString()->onEachSide(1)->links() }}
        </div>
    </div>
</x-layouts.header.public>
