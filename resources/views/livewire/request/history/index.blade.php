{{--
    View: Request History Index (Livewire)
    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)

    Description:
    - Renders a paginated table of Event requests for the current user/role.
    - Supports multi-filtering by categories, venues, and organizations.
    - Uses Bootstrap 5 for styling and Livewire pagination links.

    Variables:
    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Event> $eventhistories
    @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>} $filters

    Accessibility notes:
    - Table headings should use <th scope="col">.
    - Pagination controls are generated via $eventhistories->links() which include proper ARIA labels.
    - Action links/buttons must have discernible text for screen readers.
--}}


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
    <h1 class="h4 mb-3">Approval History</h1>


    <div class="card shadow-sm mb-3">
        <livewire:request.history.filters/>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Action</th>
                    <th>Date Submitted</th>

                    <th class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($eventhistories as $history)
                    <tr>
                        <td class="fw-medium">{{$history->event->title ?? '—' }}</td>
                        <td class="fw-medium">{{$history->event->organization_name  ?? '—' }}</td>
                        <td class="fw-medium">
                            {{ $history->action ? ucfirst(strtolower($history->action)) : '—' }}
                        </td>
                        <td class="fw-medium">{{ \Carbon\Carbon::parse($history->created_at)->format('D, M j, Y g:i A') }}</td>
                        <td class="fw-medium text-end">
                            <button type="button"
                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="View details"
                                    aria-label="View details"
                                    onclick="window.location='{{ route('approver.history.request',['eventHistory'=>$history]) }}'">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">View details</span>
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
        {{ $eventhistories->withQueryString()->onEachSide(1)->links() }}
    </div>
</div>
