<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">User Management</h1>

    <div class="d-none d-md-flex gap-2">
      <button class="btn btn-outline-success btn-sm" wire:click="restoreUsers" type="button">
        <i class="bi bi-arrow-clockwise me-1"></i> Restore Deleted
      </button>
      <button class="btn btn-primary btn-sm" wire:click="openCreate" type="button">
        <i class="bi bi-person-plus me-1"></i> Add User
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
            <input type="text" class="form-control" placeholder="Search by name or email…"
              wire:model.live.debounce.300ms="search">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label">Role</label>
          <select class="form-select" wire:model.live="role">
            <option value="">All roles</option>
            @foreach(\App\Livewire\Admin\UsersIndex::ROLES as $role)
            <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
          </select>
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
            <th>Email</th>
            <th>Department</th>
            <th>Role</th>
            <th class="text-end" style="width:140px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($rows as $user)
          <tr>
            <td>
              <x-table.select-row :row-id="$user['id']" :selected="$selected" :page-key="$page" />
            </td>
            <td class="fw-medium">{{ $user['name'] }}</td>
            <td><a href="mailto:{{ $user['email'] }}">{{ $user['email'] }}</a></td>
            <td>{{ $user['department'] ?? '—' }}</td>
            <td>
              <span class="badge {{ $this->roleClass($user['role'] ?? '') }}">
                {{ $user['role'] ?? '—' }}
              </span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $user['id'] }})" type="button">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" wire:click="delete({{ $user['id'] }})" type="button">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-secondary py-4">No users found.</td>
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

  {{-- Create/Edit User Modal --}}
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>{{ $editId ? 'Edit User' : 'Add User' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
            wire:click="$set('editId', null)"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Name</label>
              <input type="text" class="form-control @error('editName') is-invalid @enderror" required
                wire:model.live="editName" placeholder="Full Name">
              @error('editName')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control @error('editEmail') is-invalid @enderror" required
                wire:model.live="editEmail" placeholder="username@upr.edu">
              @error('editEmail')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Role</label>
              <select class="form-select @error('editRole') is-invalid @enderror" required wire:model.live="editRole">
                <option value="">Select Role</option>
                @foreach(\App\Livewire\Admin\UsersIndex::ROLES as $role)
                <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
              </select>
              @error('editRole')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Department</label>
              @if($this->hasRoleWithoutDepartment)
              <input type="text" class="form-control" value="—" disabled>
              <small class="text-muted">Only Venue Managers have departments</small>
              @else
              <select class="form-select @error('editDepartment') is-invalid @enderror"
                wire:model.live="editDepartment">
                <option value="">Select Department</option>
                @foreach(\App\Livewire\Admin\UsersIndex::DEPARTMENTS as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
              </select>
              @error('editDepartment')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              @endif
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Justification for save/delete --}}
  <x-justification id="userJustify"
    submit="{{ $this->isBulkDeleting ? 'confirmBulkDelete' : ($this->isDeleting ? 'confirmDelete' : 'confirmSave') }}"
    model="justification" :showDeleteType="$this->isDeleting || $this->isBulkDeleting" />

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="userToast" class="toast text-bg-success" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="userToastMsg">Done</div>
        <button type="button" class="btn-close  me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      // Keep the master checkbox correct (checked/indeterminate) after any render
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