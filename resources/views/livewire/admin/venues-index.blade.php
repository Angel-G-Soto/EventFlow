<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Venues</h1>

    <div class="d-none d-md-flex gap-2">
      <button class="btn btn-outline-success btn-sm" wire:click="restoreUsers" type="button">
        <i class="bi bi-arrow-clockwise me-1"></i> Restore Deleted
      </button>
      <button class="btn btn-primary btn-sm" wire:click="openCreate">
        <i class="bi bi-house-add me-1"></i> Add Venue
      </button>
      <button class="btn btn-secondary btn-sm" wire:click="openCsvModal" type="button">
        <i class="bi bi-upload me-1"></i> Add Venues by CSV
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label">Search</label>
          <form wire:submit.prevent="applySearch">
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Search by name or manager..."
                wire:model.defer="search">
            </div>
          </form>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Department</label>
          <select class="form-select" wire:model.live="department">
            <option value="">All</option>
            @foreach($departments as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Cap. Min</label>
          <input type="number" class="form-control" wire:model.live="capMin" min="0">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Cap. Max</label>
          <input type="number" class="form-control" wire:model.live="capMax" min="0">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button class="btn btn-outline-secondary w-100" wire:click="clearFilters" type="button">
            <i class="bi bi-x-circle me-1"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Page size --}}
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end mb-2">
    <div class="d-flex align-items-center gap-2">
      <label class="text-secondary small mb-0">Rows</label>
      <select class="form-select form-select-sm" style="width:auto" wire:model.live="pageSize">
        <option>10</option>
        <option>25</option>
        <option>50</option>
      </select>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Department</th>
            <th>Venue Code</th>
            <th>Capacity</th>
            <th>Manager</th>
            <th>Status</th>
            <th>Availability</th>
            <th class="text-end" style="width:140px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($rows as $v)
          <tr>
            <td class="fw-medium">{{ $v['name'] }}</td>
            <td>{{ $v['department'] }}</td>
            <td>{{ $v['room'] }}</td>
            <td>{{ $v['capacity'] }}</td>
            <td>{{ $v['manager'] }}</td>
            <td>
              <span class="badge {{ $v['status']==='Active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $v['status'] }}
              </span>
            </td>
            <td class="text-truncate" style="max-width:220px;">
              @if (isset($v['timeRanges']) && is_array($v['timeRanges']) && count($v['timeRanges']) > 0)
              @foreach ($v['timeRanges'] as $tr)
              <div class="small">
                {{ $tr['from'] ?? '' }} – {{ $tr['to'] ?? '' }}
                @if (!empty($tr['reason']))
                ({{ $tr['reason'] }})
                @endif
              </div>
              @endforeach
              @else
              <span class="text-muted small">No availability</span>
              @endif
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $v['id'] }})">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $v['id'] }})">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center text-secondary py-4">No venues found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer / Pager --}}
    <div class="card-footer d-flex align-items-center justify-content-between">
      <small class="text-secondary">
        {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }} results
      </small>
      {{ $rows->onEachSide(1)->links('partials.pagination') }}
    </div>
  </div>

  {{-- Create/Edit Venue Modal with inline Availability editor --}}
  {{-- CSV Upload Modal --}}
  <div class="modal fade" id="csvModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" wire:submit.prevent="uploadCsv">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Add Venues by CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">CSV File</label>
            <input type="file" class="form-control" wire:model="csvFile" accept=".csv">
            <small class="text-muted">CSV must include columns: name, room, capacity, department,
              features</small>
          </div>
          @error('csvFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Upload</button>
        </div>
      </form>
    </div>
  </div>
  <div class="modal fade" id="venueModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editId ? 'Edit Venue' : 'New Venue' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input class="form-control" required wire:model.live="vName">
            </div>
            <div class="col-md-3">
              <label class="form-label">Department</label>
              <select class="form-select" wire:model.live="vDepartment" required>
                <option value="">Select department</option>
                @foreach($departments as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Venue Code</label>
              <input class="form-control" required wire:model.live="vRoom">
            </div>
            <div class="col-md-2">
              <label class="form-label">Capacity</label>
              <input type="number" min="0" class="form-control" required wire:model.live="vCapacity">
            </div>
            <div class="col-md-3">
              <label class="form-label">Manager</label>
              <input class="form-control" wire:model.live="vManager" placeholder="username/email">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select class="form-select" wire:model.live="vStatus">
                <option>Active</option>
                <option>Inactive</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Features/Resources</label>
              <div class="row g-2">
                @php $features = ['Allow Teaching Online','Allow Teaching With Multimedia','Allow Teaching with
                computer','Allow Teaching']; @endphp
                @foreach($features as $f)
                <div class="col-6 col-md-4 col-lg-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $f }}" wire:model.live="vFeatures"
                      id="feat_{{ $loop->index }}">
                    <label class="form-check-label" for="feat_{{ $loop->index }}">{{ $f }}</label>
                  </div>
                </div>
                @endforeach
              </div>
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-2">Availability dates</h6>
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addTimeRange">
                  <i class="bi bi-plus-lg me-1"></i>Add
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th style="width:180px;">From</th>
                      <th style="width:180px;">To</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($timeRanges as $i=>$b)
                    <tr>
                      <td><input type="time" class="form-control form-control-sm"
                          wire:model.live="timeRanges.{{ $i }}.from"></td>
                      <td><input type="time" class="form-control form-control-sm"
                          wire:model.live="timeRanges.{{ $i }}.to">
                      </td>
                      <td class="align-top">
                        @error('timeRanges.'.$i.'.from')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('timeRanges.'.$i.'.to')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </td>
                      <td>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                          wire:click="removeTimeRange({{ $i }})">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="4" class="text-secondary">No Availability dates.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
                <div class="form-text">
                  Enter availability times as 24-hour HH:MM. End time must be after start time.
                </div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="bi me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete --}}
  <x-justification id="venueJustify" submit="{{ $this->isDeleting ? 'confirmDelete' : 'confirmSave' }}"
    model="justification" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="venueToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="venueToastMsg">Done</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
</div>