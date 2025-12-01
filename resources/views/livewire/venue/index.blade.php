
{{--    View: Manage Venues (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Displays department venues with current manager and allows reassignment.--}}
{{--    - Includes search/filter and pagination; changes restricted to same department.--}}

{{--    Variables (typical):--}}
{{--    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Venue> $venues--}}
{{--    @var \Illuminate\Support\Collection<int, \App\Models\User> $managers--}}
{{--    @var \App\Models\Department|null $department--}}

{{--    Accessibility notes:--}}
{{--    - Use data tables with <th scope="col"> and accessible action buttons.--}}
{{--    - Confirm modals must focus trap and provide aria-labelledby/aria-describedby.--}}
{{--    - Success/error alerts should use role="status"/role="alert" respectively.--}}

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

<div class="container">
    <h1 class="h4 mb-3">My Venues</h1>
    <h6 class="text-muted">{{$departmentName}}</h6>

    <div class="card shadow-sm mb-3 pb-1">
        <div class="card-body py-2" style="font-size: 1.05rem;">
            <div class="row align-items-end g-2">
                <div class="col-md-4 col-sm-12">
                    <label for="venueSearch"
                           class="form-label mb-0 small text-muted"
                           style="font-size: 1.05rem;">Search venues</label>
                    <div class="input-group input-group-sm">
                        <input id="venueSearch"
                               type="text"
                               class="form-control"
                               placeholder="Search by name or room number..."
                               wire:model.defer="searchDraft"
                               wire:keydown.enter="applySearch"
                               style="font-size: 1.05rem;">
                        <button type="button"
                                class="btn btn-secondary d-inline-flex align-items-center gap-2"
                                wire:click="applySearch"
                                aria-label="Search my venues"
                                style="font-size: 1.05rem;">
                            <i class="bi bi-search"></i>
                            <span>Search</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Room</th>
                <th >Capacity</th>

                <th class="text-center text-sm-end" style="width: 120px;">Actions</th>
            </tr>

            </thead>
            <tbody>
            @forelse($venues as $v)
            <tr>
                <td class="fw-medium">{{ $v['name'] }}</td>
                <td class="fw-medium">{{ $v['code'] }}</td>
                <td class="fw-medium">{{ $v['capacity'] }}</td>
                <td class="fw-medium text-center text-sm-end" style="width: 120px;">
                    <div class="d-inline-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-sm-end align-items-center w-100">
                        <button type="button"
                                wire:click="configure({{ $v['id'] }})"
                                class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Configure {{ $v['name'] }}"
                                aria-label="Configure {{ $v['name'] }}">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Configure</span>
                        </button>
                        <button type="button"
                                class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="View details for {{ $v['name'] }}"
                                aria-label="View details"
                                onclick="window.location='{{ route('venue.show', ['venue' => $v]) }}'">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">View details</span>
                        </button>
                    </div>
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
        {{ $venues->links() }}
    </div>
</div>
