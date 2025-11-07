
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
{{--<div class="container">--}}
{{--    <h1 class="h4 mb-3">Manage Venues</h1>--}}
{{--    <div class="card shadow-sm mb-3">--}}
{{--        <div class="card-body">--}}
{{--            <div class="row g-2">--}}
{{--                <div class="col-12 col-md-4">--}}
{{--                    <label class="form-label">Search</label>--}}
{{--                    <div class="input-group">--}}
{{--                        <span class="input-group-text"><i class="bi bi-search"></i></span>--}}
{{--                        <input type="text" class="form-control" placeholder="name, building, manager, department"--}}
{{--                               wire:model.live.debounce.300ms="search">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--        </div>--}}

{{--    </div>--}}
<
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Room</th>
                <th >Capacity</th>

                <th class="text-end">Actions</th>
            </tr>

            </thead>
            <tbody>
            @forelse($venues as $v)
            <tr>
                <td class="fw-medium">{{ $v['name'] }}</td>
                <td class="fw-medium">{{ $v['code'] }}</td>
                <td class="fw-medium">{{ $v['capacity'] }}</td>
                <td class="fw-medium text-end">
                    <button  wire:click="configure({{$v['id']}})" class="btn btn-secondary text-end" style="text-align: right" data-bs-toggle="tooltip" data-bs-placement="top" title="Configure">
                        <i class="bi bi-pencil"></i> Configure
                    </button>
                    <button class="btn btn-secondary text-end" style="text-align: right"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Configure" onclick="window.location='{{ route('venue.show',['venue'=>$v]) }}'">
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
        {{ $venues->links() }}
    </div>
</div>
