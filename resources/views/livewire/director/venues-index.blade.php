<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Department Venues</h1>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label" for="dir_venue_search">Search</label>
          <div class="input-group">
            <input id="dir_venue_search" class="form-control" placeholder="Name, room, department"
              wire:model.defer="search">
          </div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="dir_venue_department">Department</label>
          <select id="dir_venue_department" class="form-select" wire:model.live="department">
            <option value="">All departments</option>
            @foreach($departments as $d)
            <option value="{{ $d }}">{{ $d }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button class="btn btn-secondary w-100" wire:click="clearFilters" type="button" aria-label="Clear filters">
            <i class="bi bi-x-circle me-1"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Top bar --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <small class="text-secondary">
      {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
    </small>
    <div class="d-flex align-items-center gap-2">
      <label class="text-secondary small mb-0" for="dir_venue_rows">Rows</label>
      <select id="dir_venue_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="perPage">
        <option>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">
              <button class="btn btn-link p-0 text-decoration-none" wire:click="sortBy('name')"
                aria-label="Sort by name">
                Name
                @if($sortField === 'name')
                @if($sortDirection === 'asc')
                <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
                @else
                <i class="bi bi-arrow-down-short" aria-hidden="true"></i>
                @endif
                @else
                <i class="bi bi-arrow-down-up text-muted" aria-hidden="true"></i>
                @endif
              </button>
            </th>
            <th>Department</th>
            <th>Room</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Manager</th>
            <th class="text-end" style="width:160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $v)
          <tr>
            <td class="fw-medium">{{ $v['name'] }}</td>
            <td>{{ $v['department'] }}</td>
            <td>{{ $v['room'] }}</td>
            <td>{{ $v['capacity'] }}</td>
            <td>
              <span
                class="badge {{ $v['status']==='Active'?'text-bg-success':($v['status']==='Suspended'?'text-bg-warning':'text-bg-secondary') }}">
                {{ $v['status'] }}
              </span>
            </td>
            <td>{{ $v['manager'] }}</td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $v['id'] }})"
                  aria-label="Edit venue {{ $v['name'] }}" title="Edit venue {{ $v['name'] }}">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-outline-primary" wire:click="openAssign({{ $v['id'] }})"
                  aria-label="Assign manager to {{ $v['name'] }}" title="Assign manager to {{ $v['name'] }}">
                  <i class="bi bi-person-plus"></i> Assign Manager
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No venues found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted">
        {{ method_exists($rows,'total') ? $rows->total() : count($rows) }} results
      </small>
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

  {{-- Edit Venue Modal --}}
  <div class="modal fade" id="editVenue" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
      <form class="modal-content" wire:submit.prevent="saveEdit">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Edit Venue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label" for="dir_v_name">Name</label>
            <input id="dir_v_name" class="form-control @error('vName') is-invalid @enderror" wire:model.live="vName"
              required>
            @error('vName')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-2">
            <label class="form-label" for="dir_v_room">Room</label>
            <input id="dir_v_room" class="form-control @error('vRoom') is-invalid @enderror" wire:model.live="vRoom"
              required>
            @error('vRoom')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label" for="dir_v_capacity">Capacity</label>
              <input id="dir_v_capacity" type="number" min="1"
                class="form-control @error('vCapacity') is-invalid @enderror" wire:model.live="vCapacity" required>
              @error('vCapacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="dir_v_status">Status</label>
              <select id="dir_v_status" class="form-select @error('vStatus') is-invalid @enderror"
                wire:model.live="vStatus" required>
                <option>Active</option>
                <option>Suspended</option>
                <option>Inactive</option>
              </select>
              @error('vStatus')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-primary" type="submit" aria-label="Save venue">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Assign Manager Modal --}}
  <div class="modal fade" id="assignManager" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
      <form class="modal-content" wire:submit.prevent="saveAssign">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Assign Venue Manager</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="dir_assign_manager">Manager Email</label>
          <input id="dir_assign_manager" class="form-control @error('assignManager') is-invalid @enderror"
            wire:model.live="assignManager" placeholder="manager@upr.edu">
          @error('assignManager')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <small class="text-muted">Later: replace with a searchable list of users who have the “Venue Manager”
            role.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-primary" type="submit" aria-label="Confirm assignment">Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>