<div class="container">
    <h1 class="h4 mb-3">Manage Venues</h1>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="name, building, manager, department"
                               wire:model.live.debounce.300ms="search">
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
                    <button class="btn btn-outline-secondary text-end" style="text-align: right" data-bs-toggle="tooltip" data-bs-placement="top" title="Configure">
                        <i class="bi bi-pencil"></i> Configure
                    </button>
                    <button class="btn btn-outline-secondary text-end" style="text-align: right" data-bs-toggle="tooltip" data-bs-placement="top" title="Configure">
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
