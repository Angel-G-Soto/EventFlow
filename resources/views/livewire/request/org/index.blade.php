
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



<div>
    <h1 class="h4 mb-3">Request History</h1>




    <div class="card shadow-sm mb-3">
        <livewire:request.org.filters/>

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
                        <td class="fw-medium">{{$event->organization_nexo_name  ?? '—' }}</td>
                        <td class="fw-medium">{{ $event->created_at}}</td>
                        <td class="fw-medium">
                        @if($event->status === 'cancelled' || $event->status === 'withdrawn' || $event->status === 'rejected')
                                <span class="badge rounded-pill bg-danger">{{$event->status}}</span>
                            @elseif($event->status === 'approved' || $event->status === 'completed')
                                <span class="badge rounded-pill bg-success">{{$event->status}}</span>
                            @else
                                <span class="badge rounded-pill bg-warning">{{$event->status}}</span>

                        @endif
                        </td>
                        <td class="fw-medium text-end">
                            <button class="btn btn-outline-secondary text-end" style="text-align: right"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Details"
                                    onclick="window.location='{{ route('org.requests',['id'=>$event->id]) }}'">
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

