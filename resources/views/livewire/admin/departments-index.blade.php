<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Departments</h1>

    {{-- <div class="d-none d-md-flex gap-2">
      <button class="btn btn-primary btn-sm" wire:click="openCreate" aria-label="Add department">
        <i class="bi bi-building-add me-1"></i> Add Department
      </button>
    </div> --}}
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label" for="dept_search">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input id="dept_search" type="text" class="form-control" placeholder="Search by name or director"
                wire:model.defer="search">
              <button class="btn btn-secondary" type="submit" aria-label="Search">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="dept_code">Department Code</label>
          <select id="dept_code" class="form-select" wire:model.live="code">
            <option value="">All</option>
            @foreach($codes as $c)
            <option value="{{ $c }}">{{ $c }}</option>
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

  {{-- Page size --}}
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end mb-2">
    <div class="d-flex align-items-center gap-2">
      <label class="text-secondary small mb-0" for="dept_rows">Rows</label>
      <select id="dept_rows" class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
        <option>10</option>
        <option>25</option>
        <option>50</option>
      </select>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">
              <button class="btn btn-link p-0 text-decoration-none text-black fw-bold" wire:click="sortBy('name')"
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
            <th>Department Code</th>
            <th>Director</th>

            {{-- <th class="text-end" style="width:120px;">Actions</th> --}}
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $d)
          <tr>
            <td class="fw-medium">{{ $d['name'] }}</td>
            <td>{{ $d['code'] }}</td>
            <td>{{ trim($d['director'] ?? '') }}</td>
            {{--<td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $d['id'] }})"
                  aria-label="Edit department {{ $d['name'] }}" title="Edit department {{ $d['name'] }}">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $d['id'] }})"
                  aria-label="Delete department {{ $d['name'] }}" title="Delete department {{ $d['name'] }}">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>--}}
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center text-secondary py-4">No departments found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between">
      <small class="text-secondary">
        {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
      </small>
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

  {{-- Create/Edit --}}
  <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-building-gear me-2"></i>{{ $editId ? 'Edit Department' : 'New
            Department' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label required" for="dept_name">Name</label><input id="dept_name"
                class="form-control" wire:model.live="dName" required placeholder="Department name (e.g., Engineering)">
            </div>
            <div class="col-md-3"><label class="form-label required" for="dept_code_edit">Code</label><input
                id="dept_code_edit" class="form-control" wire:model.live="dCode" required placeholder="ENG"></div>
            <div class="col-md-3"><label class="form-label" for="dept_director">Director</label><input
                id="dept_director" class="form-control" wire:model.live="dDirector"
                placeholder="Director name or email"></div>

          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"
            aria-label="Cancel and close">Cancel</button>
          <button class="btn btn-primary" type="submit" aria-label="Save department"><i
              class="bi bi me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  <x-justification id="deptJustify" submit="confirmJustify" model="justification" />

  {{-- Confirm delete --}}
  <x-confirm-delete id="deptConfirm" title="Delete department"
    message="Are you sure you want to delete this department?" confirm="proceedDelete" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="deptToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="deptToastMsg">Done</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
</div>