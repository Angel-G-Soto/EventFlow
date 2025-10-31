
<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.pending.index') }}">Pending Request</a>
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
    <h1 class="h4 mb-3">Request History</h1>


    <div class="card shadow-sm mb-3">
        <livewire:request.history.approver.filters/>

        <div class="card-body">

            {{--                <div class="row g-2">--}}
            {{--                    <div class="col-6 col-md-2">--}}
            {{--                        <label class="form-label">Student Organization</label>--}}
            {{--                        <input class="form-control" placeholder="e.g. CAHSI">--}}
            {{--                    </div>--}}

            {{--                    <div class="col-6 col-md-2">--}}
            {{--                        <label class="form-label">Event Type</label>--}}
            {{--                        <input class="form-control" >--}}
            {{--                    </div>--}}

            {{--                    <div class="col-6 col-md-2">--}}
            {{--                        <label class="form-label">Venue</label>--}}
            {{--                        <input class="form-control">--}}
            {{--                    </div>--}}

            {{--                </div>--}}
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Date Submitted</th>

                    <th class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="fw-medium">{{$event->title ?? '—' }}</td>
                        <td class="fw-medium">{{$event->organization_nexo_name  ?? '—' }}</td>
                        <td class="fw-medium">{{ $event->created_at}}</td>
                        <td class="fw-medium text-end">
                            <button class="btn btn-outline-secondary text-end" style="text-align: right"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Details"
                                    onclick="window.location='{{ route('approver.history.request',['id'=>$event->id]) }}'">
                                <i class="bi bi-eye me-1"></i> View details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-secondary py-4">No requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $events->withQueryString()->onEachSide(1)->links() }}
    </div>
</div>
