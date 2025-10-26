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
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label">Search</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" placeholder="Search by name or manager..."
              wire:model.live.debounce.300ms="search">
          </div>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Department</label>
          <select class="form-select" wire:model.live="department">
            <option value="">All</option>
            <option value="Arts">Arts</option>
            <option value="Biology">Biology</option>
            <option value="Facilities">Facilities</option>
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

  {{-- Bulk + page size --}}
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
    <div class="btn-group">
      <button class="btn btn-outline-danger btn-sm" wire:click="bulkDelete" @disabled(empty($selected ?? []))
        type="button">
        <i class="bi bi-trash3 me-1"></i> Delete
      </button>
    </div>

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
            <th style="width:36px;">
              <x-table.select-all :visible-ids="$visibleIds" :page-key="$page" />
            </th>
            <th>Name</th>
            <th>Department</th>
            <th>Room Code</th>
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
            <td>
              <x-table.select-row :row-id="$v['id']" :selected="$selected" :page-key="$page" />
            </td>
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
            <td class="text-truncate" style="max-width:220px;">{{ $v['availability'] }}</td>
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
            <td colspan="10" class="text-center text-secondary py-4">No venues found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer / Pager --}}
    <div class="card-footer d-flex align-items-center justify-content-between">
      @if(method_exists($rows, 'total'))
      <small class="text-secondary">{{ $rows->total() }} result{{ $rows->total()===1?'':'s' }}</small>
      <div>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" wire:click="$set('page', 1)"
            @disabled($rows->currentPage()===1)>&laquo;</button>
          <button class="btn btn-outline-secondary" wire:click="$set('page', {{ $rows->currentPage() - 1 }})"
            @disabled($rows->currentPage()===1)>&lsaquo;</button>
          <span class="btn btn-outline-secondary disabled">
            Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}
          </span>
          <button class="btn btn-outline-secondary" wire:click="$set('page', {{ $rows->currentPage() + 1 }})"
            @disabled($rows->currentPage()===$rows->lastPage())>&rsaquo;</button>
          <button class="btn btn-outline-secondary" wire:click="$set('page', {{ $rows->lastPage() }})"
            @disabled($rows->currentPage()===$rows->lastPage())>&raquo;</button>
        </div>
      </div>
      @else
      <small class="text-secondary">{{ count($rows) }} result{{ count($rows)===1?'':'s' }}</small>
      @endif
    </div>
  </div>

  {{-- Create/Edit Venue Modal with inline Availability editor --}}
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
                <option value="Arts">Arts</option>
                <option value="Biology">Biology</option>
                <option value="Facilities">Facilities</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Room Code</label>
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
                @php $features = ['Allow Teaching Online','Allow Teaching With Multimedia','Allow Teaching wiht computer','Allow Teaching']; @endphp
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
              <label class="form-label">Notes</label>
              <textarea class="form-control" rows="3" wire:model.live="vNotes"></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-2">Availability / Blackout dates</h6>
                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="addBlackout">
                  <i class="bi bi-plus-lg me-1"></i>Add
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th style="width:180px;">From</th>
                      <th style="width:180px;">To</th>
                      <th>Reason</th>
                      <th style="width:50px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($blackouts as $i=>$b)
                    <tr>
                      <td><input type="date" class="form-control form-control-sm"
                          wire:model.live="blackouts.{{ $i }}.from"></td>
                      <td><input type="date" class="form-control form-control-sm"
                          wire:model.live="blackouts.{{ $i }}.to">
                      </td>
                      <td><input type="text" class="form-control form-control-sm"
                          wire:model.live="blackouts.{{ $i }}.reason" placeholder="optional"></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm"
                          wire:click="removeBlackout({{ $i }})">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="4" class="text-secondary">No blackout rows.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete --}}
  <x-justification id="venueJustify"
    submit="{{ $this->isBulkDeleting ? 'confirmBulkDelete' : ($this->isDeleting ? 'confirmDelete' : 'confirmSave') }}"
    model="justification" :showDeleteType="$this->isDeleting || $this->isBulkDeleting" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="venueToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="venueToastMsg">Done</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      const focusMap = new Map();

      function attachHiddenOnce(modalEl, modalId) {
        const onHidden = () => {
          const opener = focusMap.get(modalId);
          if (opener && typeof opener.focus === 'function') {
            setTimeout(() => opener.focus(), 30);
          }
          focusMap.delete(modalId);
          modalEl.removeEventListener('hidden.bs.modal', onHidden);
        };
        modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
      }

      Livewire.on('bs:open', ({ id }) => {
        const el = document.getElementById(id);
        if (!el) return;
        const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        focusMap.set(id, opener);
        const modal = new bootstrap.Modal(el);
        modal.show();
        queueMicrotask(() => {
          const first = el.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
          if (first && typeof first.focus === 'function') first.focus();
        });
        attachHiddenOnce(el, id);
      });

      Livewire.on('bs:close', ({ id }) => {
        const el = document.getElementById(id);
        if(!el) return;
        const m = bootstrap.Modal.getInstance(el);
        if(m) m.hide();
      });

      Livewire.on('toast', ({ message }) => {
        document.getElementById('venueToastMsg').textContent = message || 'Done';
        const toastEl = document.getElementById('venueToast');
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2200 });
        toast.show();
      });

      Livewire.on('selectionHydrate', ({ visible, selected }) => {
        const master = document.getElementById('master');
        if (!master) return;
        const set = new Set(selected);
        const onPageSelected = visible.filter(id => set.has(id));
        master.indeterminate = onPageSelected.length > 0 && onPageSelected.length < visible.length;
        master.checked = visible.length > 0 && onPageSelected.length === visible.length;
      });
    });
  </script>
</div>