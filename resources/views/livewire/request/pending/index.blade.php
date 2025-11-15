
{{--    View: Index (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Renders a paginated, filterable table/list (e.g., events/requests).--}}
{{--    - Integrates with Livewire for reactive filtering and pagination links.--}}

{{--    Variables (typical):--}}
{{--    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Event> $items--}}
{{--    @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>} $filters--}}

{{--    Accessibility notes:--}}
{{--    - Use <th scope="col"> for headers; give rows <th scope="row"> if first cell is a label.--}}
{{--    - Ensure interactive elements have discernible text; use aria-labels as needed.--}}
{{--    - Pagination via $items->links() includes ARIA attributes; place it within <nav>.--}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto">
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('public.calendar') }}">Home</a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.pending.index') }}">Pending Request</a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.history.index') }}">Request History</a>
        </li>

{{--        <li class="nav-item">--}}
{{--            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">My Venues</a>--}}
{{--        </li>--}}

    </ul>

</x-slot:pageActions>
    <div>
        <h1 class="h4 mb-3">Pending Requests</h1>


        <div wire:ignore class="card shadow-sm mb-3 pb-1">
            <livewire:request.pending.filters/>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Organization</th>
                        <th>Date Submitted</th>
                        <th>Status</th>

                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td class="fw-medium">{{$event->title ?? '—' }}</td>
                                <td class="fw-medium">{{$event->organization_name  ?? '—' }}</td>
                                <td class="fw-medium">{{ \Carbon\Carbon::parse($event->created_at)->format('D, M j, Y g:i A') }}</td>
                                <td class="fw-medium">
                                    @if($event->status === 'cancelled' || $event->status === 'withdrawn' || $event->status === 'rejected')
                                        <span class="badge rounded-pill bg-danger">{{$event->getSimpleStatus()}}</span>
                                    @elseif($event->status === 'approved' || $event->status === 'completed')
                                        <span class="badge rounded-pill bg-success">{{$event->getSimpleStatus()}}</span>
                                    @else
                                        <span class="badge rounded-pill bg-warning">{{$event->getSimpleStatus()}}</span>

                                    @endif
                                </td>
                                <td class="fw-medium text-end">
                                    <button class="btn btn-outline-secondary text-end" style="text-align: right"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="View Details"
                                            onclick="window.location='{{ route('approver.pending.request',['event'=>$event]) }}'">
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
