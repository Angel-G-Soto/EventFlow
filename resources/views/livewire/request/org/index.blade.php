
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
        {{--        <li class="nav-item">--}}
        {{--            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('approver.pending.index') }}">Pending Request</a>--}}
        {{--        </li>--}}

        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.history.index') ? 'active' : '' }} " href="{{ route('approver.history.index') }}">Request History</a>
        </li>

        {{--        <li class="nav-item">--}}
        {{--            <a class="fw-bold nav-link ? 'active' : '' " href="{{ route('home') }}">My Venues</a>--}}
        {{--        </li>--}}

    </ul>

</x-slot:pageActions>

<div>
    <h1 class="h4 mb-3">My Requests</h1>




    <div class="card shadow-sm mb-3">
        <livewire:request.org.filters/>

    </div>

    <style>
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-indicator .status-dot {
            width: 0.5rem;
            height: 0.5rem;
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

        .status-indicator--warning {
            color: #856404;
        }
    </style>

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
                            @php
                                $statusVariant = match (true) {
                                    in_array($event->status, ['cancelled', 'withdrawn', 'rejected']) => 'danger',
                                    in_array($event->status, ['approved', 'completed']) => 'success',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="status-indicator status-indicator--{{ $statusVariant }}">
                                <span class="status-dot" aria-hidden="true"></span>
                                <span>{{ $event->getSimpleStatus() }}</span>
                            </span>
                        </td>
                        <td class="fw-medium text-end">
                            <button type="button"
                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="View details"
                                    aria-label="View details"
                                    onclick="window.location='{{ route('user.request',['event'=>$event]) }}'">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">View details</span>
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
