<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">User Management</h1>

    <div class="d-none d-md-flex gap-2">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
        <i class="bi bi-person-plus me-1"></i> Invite User
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
            <input
              type="text"
              class="form-control"
              placeholder="Search by name or email…"
              wire:model.live.debounce.300ms="search"
            >
          </div>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label">Role</label>
          <select class="form-select" wire:model.live="role">
            <option value="">All roles</option>
            <option>Student</option>
            <option>Approver</option>
            <option>Space Manager</option>
            <option>Admin</option>
          </select>
        </div>


        <div class="col-12 col-md-2 d-flex align-items-end">
          <button
            class="btn btn-outline-secondary w-100"
            wire:click="$set('search','');$set('role','')"
            type="button"
          >
            <i class="bi bi-x-circle me-1"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Bulk + page size --}}
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
    <div class="btn-group">
      <button class="btn btn-outline-secondary btn-sm" wire:click="bulkActivate" @disabled(empty($selected ?? []))>
        <i class="bi bi-check2-circle me-1"></i> Activate
      </button>
      <button class="btn btn-outline-secondary btn-sm" wire:click="bulkSuspend" @disabled(empty($selected ?? []))>
        <i class="bi bi-slash-circle me-1"></i> Suspend
      </button>
      <button class="btn btn-outline-danger btn-sm" wire:click="bulkDelete" @disabled(empty($selected ?? []))>
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
              <input
                type="checkbox"
                class="form-check-input"
                wire:change="selectAllOnPage($event.target.checked, @json($visibleIds ?? []))"
              >
            </th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th class="text-end" style="width:140px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($rows as $u)
            <tr>
              <td>
                <input
                  type="checkbox"
                  class="form-check-input"
                  wire:change="toggleSelect({{ $u['id'] }}, $event.target.checked)"
                  @checked(data_get($selected ?? [], $u['id'], false))
                >
              </td>
              <td class="fw-medium">{{ $u['name'] }}</td>
              <td><a href="mailto:{{ $u['email'] }}">{{ $u['email'] }}</a></td>
              <td>
                <span class="badge {{ $this->roleClass($u['role'] ?? '') }}">
                  {{ $u['role'] ?? '—' }}
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary" wire:click="openEdit({{ $u['id'] }})" type="button">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-outline-danger" wire:click="delete({{ $u['id'] }})" type="button">
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
            <button class="btn btn-outline-secondary" wire:click="$set('page',1)" @disabled($rows->currentPage()===1)>&laquo;</button>
            <button class="btn btn-outline-secondary" wire:click="$set('page', max(1, $page-1))" @disabled($rows->currentPage()===1)>&lsaquo;</button>
            <span class="btn btn-outline-secondary disabled">
              Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}
            </span>
            <button class="btn btn-outline-secondary" wire:click="$set('page', min($rows->lastPage(), $page+1))" @disabled($rows->currentPage()===$rows->lastPage())>&rsaquo;</button>
            <button class="btn btn-outline-secondary" wire:click="$set('page', $rows->lastPage())" @disabled($rows->currentPage()===$rows->lastPage())>&raquo;</button>
          </div>
        </div>
      @else
        <small class="text-secondary">{{ count($rows) }} result{{ count($rows)===1?'':'s' }}</small>
      @endif
    </div>
  </div>

  {{-- Edit Modal --}}
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" wire:submit.prevent="save">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                  wire:click="$set('editId', null)"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" required wire:model.live="editName">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" required wire:model.live="editEmail">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Role</label>
              <select class="form-select" required wire:model.live="editRole">
                <option>Student</option>
                <option>Approver</option>
                <option>Space Manager</option>
                <option>Admin</option>
              </select>
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

  {{-- Invite Modal (placeholder so the header button works) --}}
  <div class="modal fade" id="inviteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Invite User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="email" class="form-control" placeholder="email@upr.edu">
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary">Send Invite</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Toast --}}
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;" wire:ignore>
    <div id="appToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive">
      <div class="d-flex">
        <div class="toast-body" id="toastMsg">Saved!</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  {{-- Livewire ↔ Bootstrap JS bridge --}}
  <script>
    document.addEventListener('livewire:init', () => {
      const toastEl = document.getElementById('appToast');

      Livewire.on('bs:open', ({ id }) => {
        const el = document.getElementById(id);
        if (el) new bootstrap.Modal(el).show();
      });

      Livewire.on('bs:close', ({ id }) => {
        const el = document.getElementById(id);
        if (!el) return;
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
      });

      Livewire.on('toast', ({ message }) => {
        document.getElementById('toastMsg').textContent = message || 'Done';
        new bootstrap.Toast(toastEl, { delay: 2200 }).show();
      });
    });
  </script>
</div>
