<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Departments</h1>

    <div class="d-none d-md-flex gap-2">
      <button class="btn btn-outline-success btn-sm" wire:click="restoreUsers" type="button">
        <i class="bi bi-arrow-clockwise me-1"></i> Restore Deleted
      </button>
      <button class="btn btn-primary btn-sm" wire:click="openCreate">
        <i class="bi bi-building-add me-1"></i> Add Department
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label">Search</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" placeholder="Search by name, code, or director..."
              wire:model.live.debounce.300ms="search">
          </div>
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

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px;">
              <x-table.select-all :visible-ids="$visibleIds" :page-key="$page" />
            </th>
            <th>Name</th>
            <th>Code</th>
            <th>Director</th>
            <th>Contact</th>
            <th>Venues</th>
            <th>Members</th>
            <th class="text-end" style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $d)
          <tr>
            <td>
              <x-table.select-row :row-id="$d['id']" :selected="$selected" :page-key="$page" />
            </td>
            <td class="fw-medium">{{ $d['name'] }}</td>
            <td>{{ $d['code'] }}</td>
            <td>{{ $d['director'] }}</td>
            <td>
              <div>{{ $d['email'] }}</div>
            </td>
            <td>
              {{ $d['venues'] }}
            </td>
            <td>{{ $d['members'] }}</td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $d['id'] }})">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $d['id'] }})">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center text-secondary py-4">No departments found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between">
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
    </div>
  </div>

  {{-- Create/Edit --}}
  <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-building-gear me-2"></i>{{ $editId ? 'Edit Department' : 'New
            Department' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control"
                wire:model.live="dName" required></div>
            <div class="col-md-3"><label class="form-label">Code</label><input class="form-control"
                wire:model.live="dCode" required></div>
            <div class="col-md-3"><label class="form-label">Director (user)</label><input class="form-control"
                wire:model.live="dDirector"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control"
                wire:model.live="dEmail"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control"
                wire:model.live="dPhone"></div>
            <div class="col-12"><label class="form-label">Default Policies</label><textarea class="form-control"
                rows="4" wire:model.live="dPolicies"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  <x-justification id="deptJustify"
    submit="{{ $this->isBulkDeleting ? 'confirmBulkDelete' : ($this->isDeleting ? 'confirmDelete' : 'confirmSave') }}"
    model="justification" :showDeleteType="$this->isDeleting || $this->isBulkDeleting" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="deptToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="deptToastMsg">Done</div>
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
        document.getElementById('deptToastMsg').textContent = message || 'Done';
        const toastEl = document.getElementById('deptToast');
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