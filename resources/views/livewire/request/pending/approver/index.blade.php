
    <div>
        <h1 class="h4 mb-3">Pending Requests</h1>




        <div class="card shadow-sm mb-3">
            <livewire:filters/>

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
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Configure"
                                            onclick="window.location='{{ route('approver.requests',['id'=>$event->id]) }}'">
                                        <i class="bi bi-eye me-1"></i> View details
                                    </button>
                                </td>

                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-secondary py-4">No venues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $events->withQueryString()->onEachSide(1)->links() }}
        </div>
    </div>

